import {describe, expect, test} from '@jest/globals';
import {makeBlock, setupCodeMatcher} from '../shared.js';
import optionsWithSetup from '../../injectors/optionsWithSetup.js';

setupCodeMatcher();

describe('optionsWithSetup', () => {
  test('handles code with no useFusion call', () => {
    const code = `
    import something from 'somewhere';
    export default {
      setup() {
        const x = 1;
      }
    }
    `;
    const script = makeBlock(code);
    const keys = ['name', 'email'];

    const [rewritten, remainingKeys] = optionsWithSetup(code, script, keys);

    const expected = `
let __fusionProvidedProps;
import something from 'somewhere';

const __default__ = {
  setup() {
    const x = 1;
  }
}

import { useFusion } from "__aliasedFusionPath__";

const userSetup = __default__.setup;
__default__.setup = function(props, ctx) {
  __fusionProvidedProps = props;
  const fusionData = useFusion([__exportedKeysAsQuotedCsv__], props.fusion || {});
  let userReturns = typeof userSetup === 'function' ? userSetup(props, ctx) : {};
  return { ...fusionData, ...userReturns };
};
export default __default__;
    `;
    expect(remainingKeys).toEqual(['name', 'email']);
    expect(rewritten).toMatchCode(expected);
  });

  test('handles code with useFusion import but no parameters', () => {
    const code = `
      import { useFusion } from '@/lib/fusion';
      export default {
        setup() {
          const { data } = useFusion();
        }
      }    
    `;
    const script = makeBlock(code);
    const keys = ['name', 'email'];

    const [rewritten, remainingKeys] = optionsWithSetup(code, script, keys);

    const expected = `
      let __fusionProvidedProps;
      const __default__ = {
        setup() {
          const { data } = useFusion([__exportedKeysAsQuotedCsv__], __fusionProvidedProps.fusion || {}, true);
        }
      }
      import { useFusion } from "__aliasedFusionPath__";
      
      const userSetup = __default__.setup;
      __default__.setup = function(props, ctx) {
        __fusionProvidedProps = props;
        const fusionData = {};
        let userReturns = typeof userSetup === 'function' ? userSetup(props, ctx) : {};
        return { ...fusionData, ...userReturns };
      };
      export default __default__;     
    `;

    expect(remainingKeys).toEqual([]);
    expect(rewritten).toMatchCode(expected);
  });

  test('handles code with useFusion import and specific keys', () => {
    const code = `
      import { useFusion } from '@/lib/fusion';
      export default {
        setup() {
          const { data } = useFusion(['name']);
        }
      }       
    `;
    const script = makeBlock(code);
    const keys = ['name', 'email'];

    const [rewritten, remainingKeys] = optionsWithSetup(code, script, keys);

    const expected = `
let __fusionProvidedProps;    
const __default__ = {
  setup() {
    const { data } = useFusion(['name'], __fusionProvidedProps.fusion || {}, true);
  }
}
import { useFusion } from "__aliasedFusionPath__";

const userSetup = __default__.setup;
__default__.setup = function(props, ctx) {
  __fusionProvidedProps = props;
  const fusionData = useFusion([__exportedKeysAsQuotedCsv__], props.fusion || {});
  let userReturns = typeof userSetup === 'function' ? userSetup(props, ctx) : {};
  return { ...fusionData, ...userReturns };
};
export default __default__;    
    `;
    expect(remainingKeys).toEqual(['email']);
    expect(rewritten).toMatchCode(expected);
  });

  test('handles empty array parameter', () => {
    const code = `
      import { useFusion } from '@/lib/fusion';
      export default {
        setup() {
          const result = useFusion([]);
        }
      }   
    `;
    const script = makeBlock(code);
    const keys = ['name', 'email'];

    const [rewritten, remainingKeys] = optionsWithSetup(code, script, keys);

    // An empty array means no keys are handled, so all keys should be injected.
    const expected = `
let __fusionProvidedProps;    
const __default__ = {
  setup() {
    const result = useFusion([], __fusionProvidedProps.fusion || {}, true);
  }
}
import { useFusion } from "__aliasedFusionPath__";

const userSetup = __default__.setup;
__default__.setup = function(props, ctx) {
  __fusionProvidedProps = props;
  const fusionData = useFusion([__exportedKeysAsQuotedCsv__], props.fusion || {});
  let userReturns = typeof userSetup === 'function' ? userSetup(props, ctx) : {};
  return { ...fusionData, ...userReturns };
};
export default __default__;    
    `;
    expect(remainingKeys).toEqual(['name','email']);
    expect(rewritten).toMatchCode(expected);
  });

  test('handles whitespace variations', () => {
    const code = `
      import { useFusion } from '@/lib/fusion';
      export default {
        setup() {
          const { data } = useFusion(['name']);
        }
      }     
    `;
    const script = makeBlock(code);
    const keys = ['name', 'email'];

    const [rewritten, remainingKeys] = optionsWithSetup(code, script, keys);

    const expected = `
let __fusionProvidedProps;    
const __default__ = {
  setup() {
    const { data } = useFusion(['name'], __fusionProvidedProps.fusion || {}, true);
  }
}
import { useFusion } from "__aliasedFusionPath__";

const userSetup = __default__.setup;
__default__.setup = function(props, ctx) {
  __fusionProvidedProps = props;
  const fusionData = useFusion([__exportedKeysAsQuotedCsv__], props.fusion || {});
  let userReturns = typeof userSetup === 'function' ? userSetup(props, ctx) : {};
  return { ...fusionData, ...userReturns };
};
export default __default__;    
    `;
    expect(remainingKeys).toEqual(['email']);
    expect(rewritten).toMatchCode(expected);
  });

  test('handles useFusion call with multiline parameter formatting', () => {
    const code = `
      import { useFusion } from '@/lib/fusion';
      export default {
        setup() {
          const { data } = useFusion([
            'name',
            'email'
          ]);
        }
      } 
    `;
    const script = makeBlock(code);
    const keys = ['name', 'email', 'phone'];

    const [rewritten, remainingKeys] = optionsWithSetup(code, script, keys);

    const expected = `
let __fusionProvidedProps;    
const __default__ = {
  setup() {
    const { data } = useFusion([ 'name', 'email' ], __fusionProvidedProps.fusion || {}, true);
  }
}
import { useFusion } from "__aliasedFusionPath__";

const userSetup = __default__.setup;
__default__.setup = function(props, ctx) {
  __fusionProvidedProps = props;
  const fusionData = useFusion([__exportedKeysAsQuotedCsv__], props.fusion || {});
  let userReturns = typeof userSetup === 'function' ? userSetup(props, ctx) : {};
  return { ...fusionData, ...userReturns };
};
export default __default__;    
    `;
    expect(remainingKeys).toEqual(['phone']);
    expect(rewritten).toMatchCode(expected);
  });

  test('handles useFusion import with other imports', () => {
    const code = `
      import { something } from 'somewhere';
      import { useFusion, otherThing } from '@/lib/fusion';
      import another from 'another-place';
      export default {
        setup() {
          const { data } = useFusion(['name']);
        }
      }
    `;
    const script = makeBlock(code);
    const keys = ['name', 'email'];

    const [rewritten, remainingKeys] = optionsWithSetup(code, script, keys);

    const expected = `
      let __fusionProvidedProps;
      import { something } from 'somewhere';
      import another from 'another-place';
      
      const __default__ = {
        setup() {
          const { data } = useFusion(['name'], __fusionProvidedProps.fusion || {}, true);
        }
      }
      import { useFusion } from "__aliasedFusionPath__";
      
      const userSetup = __default__.setup;
      __default__.setup = function(props, ctx) {
        __fusionProvidedProps = props;
        const fusionData = useFusion([__exportedKeysAsQuotedCsv__], props.fusion || {});
        let userReturns = typeof userSetup === 'function' ? userSetup(props, ctx) : {};
        return { ...fusionData, ...userReturns };
      };
      export default __default__;
    `;
    expect(remainingKeys).toEqual(['email']);
    expect(rewritten).toMatchCode(expected);
  });

  test('handles double quoted strings in useFusion parameters', () => {
    const code = `
      import { useFusion } from '@/lib/fusion';
      export default {
        setup() {
          const { data } = useFusion(["name"]);
        }
      }    
    `;
    const script = makeBlock(code);
    const keys = ['name', 'email'];

    const [rewritten, remainingKeys] = optionsWithSetup(code, script, keys);

    const expected = `
let __fusionProvidedProps;    
const __default__ = {
  setup() {
    const { data } = useFusion(["name"], __fusionProvidedProps.fusion || {}, true);
  }
}
import { useFusion } from "__aliasedFusionPath__";

const userSetup = __default__.setup;
__default__.setup = function(props, ctx) {
  __fusionProvidedProps = props;
  const fusionData = useFusion([__exportedKeysAsQuotedCsv__], props.fusion || {});
  let userReturns = typeof userSetup === 'function' ? userSetup(props, ctx) : {};
  return { ...fusionData, ...userReturns };
};
export default __default__;
    `;
    expect(remainingKeys).toEqual(['email']);
    expect(rewritten).toMatchCode(expected);
  });

  test('handles mixed quote types in parameters', () => {
    const code = `
      import { useFusion } from '@/lib/fusion';
      export default {
        setup() {
          const { data } = useFusion(['name', "email"]);
        }
      }     
    `;
    const script = makeBlock(code);
    const keys = ['name', 'email', 'phone'];

    const [rewritten, remainingKeys] = optionsWithSetup(code, script, keys);

    const expected = `
      let __fusionProvidedProps;
      const __default__ = {
        setup() {
          const { data } = useFusion(['name', "email"], __fusionProvidedProps.fusion || {}, true);
        }
      }
      import { useFusion } from "__aliasedFusionPath__";
      
      const userSetup = __default__.setup;
      __default__.setup = function(props, ctx) {
        __fusionProvidedProps = props;
        const fusionData = useFusion([__exportedKeysAsQuotedCsv__], props.fusion || {});
        let userReturns = typeof userSetup === 'function' ? userSetup(props, ctx) : {};
        return { ...fusionData, ...userReturns };
      };
      export default __default__;
    `;
    expect(remainingKeys).toEqual(['phone']);
    expect(rewritten).toMatchCode(expected);
  });

  test('handles useFusion call with trailing comma in parameters', () => {
    const code = `
      import { useFusion } from '@/lib/fusion';
      export default {
        setup() {
          const data = useFusion(['name',]);
        }
      }      
    `;
    const script = makeBlock(code);
    const keys = ['name', 'email'];

    const [rewritten, remainingKeys] = optionsWithSetup(code, script, keys);

    // The trailing comma should be preserved.
    const expected = `
let __fusionProvidedProps;    
const __default__ = {
  setup() {
    const data = useFusion(['name',], __fusionProvidedProps.fusion || {}, true);
  }
}
import { useFusion } from "__aliasedFusionPath__";

const userSetup = __default__.setup;
__default__.setup = function(props, ctx) {
  __fusionProvidedProps = props;
  const fusionData = useFusion([__exportedKeysAsQuotedCsv__], props.fusion || {});
  let userReturns = typeof userSetup === 'function' ? userSetup(props, ctx) : {};
  return { ...fusionData, ...userReturns };
};
export default __default__;    
    `;
    expect(remainingKeys).toEqual(['email']);
    expect(rewritten).toMatchCode(expected);
  });

  test('throws error if useFusion is imported with an alias', () => {
    const code = `
      import { useFusion as uf } from '@/lib/fusion';
      const data = uf(['name']);
    `;
    const script = makeBlock(code);
    const keys = ['name', 'email'];

    expect(() => optionsWithSetup(code, script, keys)).toThrow("Aliased useFusion import is not allowed.");
  });

  test('throws error if useFusion is called twice', () => {
    const code = `
      import { useFusion } from '@/lib/fusion';
      const a = useFusion(['name']);
      const b = useFusion(['email']);
    `;
    const script = makeBlock(code);
    const keys = ['name', 'email', 'phone'];

    expect(() => optionsWithSetup(code, script, keys)).toThrow("Multiple useFusion calls are not allowed.");
  });

  test('import lname only', () => {
    const code = `
      import {useFusion} from "$fusion/Pages/Imports/ScriptSetup.js";
      
      export default {
        setup() {
          const {lname} = useFusion(['lname']);
      
          lname.value = 'Smith';
      
          return {lname}
        }
      }    
    `;

    const script = makeBlock(code);
    const keys = ['fname', 'lname'];

    const [rewritten, remainingKeys] = optionsWithSetup(code, script, keys);

    const expected = `
let __fusionProvidedProps;

const __default__ = {
  setup() {
    const {lname} = useFusion(['lname'], __fusionProvidedProps.fusion || {}, true);
    lname.value = 'Smith';
    return {lname}
  }
}
import { useFusion } from "__aliasedFusionPath__";

const userSetup = __default__.setup;
__default__.setup = function(props, ctx) {
  __fusionProvidedProps = props;
  const fusionData = useFusion([__exportedKeysAsQuotedCsv__], props.fusion || {});
  let userReturns = typeof userSetup === 'function' ? userSetup(props, ctx) : {};
  return { ...fusionData, ...userReturns };
};
export default __default__;    
    `;
    expect(remainingKeys).toEqual(['fname']);
    expect(rewritten).toMatchCode(expected);
  })
});