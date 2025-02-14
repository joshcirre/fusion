import {reactive, unref, isRef} from "vue";
import Pipeline from "./Pipeline.js";
// @TODO we can rely on this because Inertia relies on it, but should we?
import axios from "axios";

export default class ActionFactory {
  constructor(keys, state) {
    const actions = {};
    const fusionProvidedActions = {};

    keys.forEach((key) => {
      // Create the action function.
      const actionFunction = (function () {
        let recentlyFailedTimeout;
        let recentlySucceededTimeout;
        let recentlyFinishedTimeout;

        const status = reactive({
          processing: false,

          failed: false,
          recentlyFailed: false,

          succeeded: false,
          recentlySucceeded: false,

          finished: false,
          recentlyFinished: false,

          error: null,
          errors: [],
        });

        const fn = async function (args = {}, body = {}) {
          // Reset states before a new request
          status.processing = true;

          status.failed = false;
          status.recentlyFailed = false;

          status.succeeded = false;
          status.recentlySucceeded = false;

          status.finished = false;
          status.recentlyFinished = false;

          status.error = null;
          status.errors = [];

          clearTimeout(recentlyFailedTimeout);
          clearTimeout(recentlySucceededTimeout);
          clearTimeout(recentlyFinishedTimeout);

          // If the call comes directly from a Vue template (e.g.
          // click or keypress), ignore the event object.
          if (args instanceof Event) {
            args = {};
          }

          let fusion = {
            args,
            state: {}
          };

          Object.keys(state).forEach((key) => {
            fusion.state[key] = unref(state[key]);
          });

          body.fusion = fusion;

          try {
            const response = await axios.post('', body, {
              headers: {
                'X-Fusion-Action-Request': 'true',
                'X-Fusion-Action-Handler': key,
              }
            });

            // Mark as succeeded
            status.succeeded = true;
            status.recentlySucceeded = true;

            recentlySucceededTimeout = setTimeout(() => {
              status.recentlySucceeded = false;
            }, 3500);

            const newState = new Pipeline(response.data?.fusion || {}).createState();

            Object.keys(newState).forEach((key) => {
              if (key in state && isRef(state[key])) {
                state[key].value = unref(newState[key]);
              }
            });

            return response.data;
          } catch (error) {
            // If it's a 422, populate the validation errors
            if (error.response && error.response.status === 422) {
              status.error = error.response.data.message;
              status.errors = error.response.data.errors ?? {};
            }

            // Mark as failed
            status.failed = true;
            status.recentlyFailed = true;

            recentlyFailedTimeout = setTimeout(() => {
              status.recentlyFailed = false;
            }, 3500);

            throw error;
          } finally {
            // Mark as finished (both success or fail)
            status.finished = true;
            status.recentlyFinished = true;

            recentlyFinishedTimeout = setTimeout(() => {
              status.recentlyFinished = false;
            }, 3500);

            status.processing = false;
          }
        };

        // Return a Proxy so that you can call the function directly and also access its reactive status
        return new Proxy(fn, {
          get(target, prop, receiver) {
            if (prop === "getStatus") {
              return () => status;
            }

            if (Object.prototype.hasOwnProperty.call(status, prop)) {
              return status[prop];
            }

            return Reflect.get(target, prop, receiver);
          },

          set(target, prop, value) {
            if (Object.prototype.hasOwnProperty.call(status, prop)) {
              status[prop] = value;
              return true;
            }

            return Reflect.set(target, prop, value);
          }
        });
      })();

      // If the key starts with 'fusion', add it to fusionProvidedActions with a modified key
      if (key.startsWith("fusion")) {
        // Remove the 'fusion' prefix and lowercase the first character of the remaining string
        const trimmed = key.slice("fusion".length); // e.g. "Sync" from "fusionSync"
        const newKey = trimmed.charAt(0).toLowerCase() + trimmed.slice(1);
        fusionProvidedActions[newKey] = actionFunction;
      } else {
        // Save the action using its original key
        actions[key] = actionFunction;
      }
    });

    // Nest fusionProvidedActions under the `fusion` key in actions
    actions.fusion = fusionProvidedActions;

    return actions;
  }
}