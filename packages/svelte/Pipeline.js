import fusionProvidedActions from "./actions/index.js";

export default class Pipeline {
  constructor(response) {
    this.stack = [];

    // This serves as the initial payload that will be passed to
    // the very first action. After that each action should
    // return the payload to be passed to the next one.
    this.initial = {
      fusion: response,
      state: {}
    };

    response?.actions?.forEach((action) => {
      this.use({
        priority: action.priority,
        // Append the action itself to the middleware, as it often
        // contains params that were added on the server side.
        action: {...action},

        // @TODO user-defined handlers?
        handler: fusionProvidedActions[action.handler] ?? function () {
          throw new Error(`No handler exported for [${action.handler}].`)
        }
      });
    });
  }

  use(middleware) {
    if (typeof middleware.handler !== 'function' || typeof middleware.priority !== 'number') {
      throw new Error(`Invalid middleware: expected an object with a numeric 'priority' and a 'handler' function`);
    }

    this.stack.push(middleware);

    return this;
  }

  run() {
    let index = 0;

    this.stack.sort((a, b) => a.priority - b.priority);

    const execute = (carry) => {
      // Possible carryover from the previous iteration.
      // Delete it just to be safe.
      delete carry.action;

      // No more actions!
      if (index >= this.stack.length) {
        return carry;
      }

      const {handler, action} = this.stack[index++];

      // Pass context and a `next` function for each middleware to call.
      let response = handler({...carry, action, pipeline: this}, execute);

      // An easy way to return a noop or a "pass" is to just return
      // the `execute` function (unexecuted!) from the middleware.
      if (response === execute) {
        return execute(carry);
      }

      return response;
    };

    return execute(this.initial);
  }

  createState() {
    return this.run()?.state || {}
  }
}