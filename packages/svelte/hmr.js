import {unref, isRef} from "@vue/reactivity";
import Pipeline from "./Pipeline.js";

const store = {};

export function noop() {

}

export default function useHotFusion(fusion, options = {}) {
  const {hot, id} = options;

  if (!hot || !id) {
    return;
  }

  new HMR(id, hot, fusion).init();
}

class HMR {
  constructor(id, hot, fusion) {
    this.id = id;
    this.hot = hot;
    this.fusion = fusion;
  }

  init() {
    this.state = store[this.id] ??= {
      initialLoad: true,
      previousKeys: [],
      previousData: {},
      reset() {
        delete store[this.id];
      },
    };

    // Bind methods so we can remove them in removeListeners
    this.boundHandleBeforeUpdate = this.handleBeforeUpdate.bind(this);
    this.boundHandleAfterUpdate = this.handleAfterUpdate.bind(this);
    this.boundHandleBeforeFullReload = this.handleBeforeFullReload.bind(this);

    this.addListeners();

    if (this.state.initialLoad) {
      this.initialLoad();
    }
  }

  addListeners() {
    this.hot.on('vite:beforeUpdate', this.boundHandleBeforeUpdate);
    this.hot.on('vite:afterUpdate', this.boundHandleAfterUpdate);
    this.hot.on('vite:beforeFullReload', this.boundHandleBeforeFullReload);
  }

  removeListeners() {
    this.hot.off('vite:beforeUpdate', this.boundHandleBeforeUpdate);
    this.hot.off('vite:afterUpdate', this.boundHandleAfterUpdate);
    this.hot.off('vite:beforeFullReload', this.boundHandleBeforeFullReload);
  }

  initialLoad() {
    this.fetchHotData();
    this.state.initialLoad = false;
  }

  handleBeforeFullReload() {
    this.removeListeners();
    this.state.reset();
    this.stashExistingData();
  }

  handleBeforeUpdate(payload) {
    this.whenUpdated(payload, this.stashExistingData);
  }

  handleAfterUpdate(payload) {
    this.whenUpdated(payload, this.fetchHotData);
  }

  stashExistingData() {
    this.state.previousData = Object.keys(this.fusion).reduce((carry, key) => {
      carry[key] = unref(this.fusion[key]);
      return carry;
    }, {});
  }

  fetchHotData() {
    this.applyStashedData();

    axios.post('', {}, {
      headers: {
        'X-Fusion-Hmr-Request': 'true',
      }
    })
      .then((response) => {
        const newState = new Pipeline(response.data?.fusion || {}).createState()
        const newKeys = Object.keys(newState);

        const keysChanged = this.state.previousKeys.length && !arraysShallowEqual(this.state.previousKeys, newKeys);

        if (keysChanged) {
          // They added or removed a prop, so we should just fully reload.
          this.removeListeners();
          window.location.reload();
        } else {
          this.state.previousKeys = newKeys;
          this.applyData(newState);
        }
      })
      .catch(() => {
        console.warn(`[HMR:${this.id}] Hot data fetch failed.`);
      });
  }

  applyStashedData() {
    this.applyData(this.state.previousData);
  }

  applyData(data = {}) {
    Object.keys(data).forEach((key) => {
      if (key in this.fusion && isRef(this.fusion[key])) {
        // Coming out of the pipeline it'll be a ref. Coming from previously
        // stashed data it'll be a raw value. `unref` handles both.
        this.fusion[key].value = unref(data[key]);
      }
    });
  }

  whenUpdated(payload, callback) {
    // Make sure this HMR update applies to this component.
    if (payload.updates.some(u => u.path === this.hot.ownerPath)) {
      callback.call(this);
    }
  }
}

function arraysShallowEqual(arr1, arr2) {
  if (arr1.length !== arr2.length) return false;
  for (let i = 0; i < arr1.length; i++) {
    if (arr1[i] !== arr2[i]) return false;
  }
  return true;
}