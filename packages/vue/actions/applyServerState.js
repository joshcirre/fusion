import {ref, unref} from 'vue';

export default function applyServerState ({fusion, state}, next) {
  Object.keys(fusion.state).forEach(key => {
    state[key] = ref(unref(fusion.state[key]))
  });

  return next({fusion, state});
}