import {describe, expect, test} from '@jest/globals';
import {makeBlock, setupCodeMatcher} from '../shared.js';
import injector from '../../injectors/modifyScriptSetup.js';


// Setup the custom matcher
setupCodeMatcher();

describe('injector', () => {
  test('handles code with no useFusion import', () => {
    const code = `
    import something from 'somewhere';
    const x = 1;
  `;

    const script = makeBlock(code);
    const keys = ['name', 'email'];

    const [rewritten, remainingKeys] = injector(code, script, keys);

    expect(remainingKeys).toEqual(keys);
    expect(rewritten).toMatchCode(`
      import { useFusion } from "__aliasedFusionPath__"; 
      import something from 'somewhere'; 
      const {__exportedKeysAsCsv__} = useFusion([__exportedKeysAsQuotedCsv__], __props.fusion); 
      const x = 1;
  `);
  });

  test('handles code with useFusion import but no parameters', () => {
    const code = `
      import { useFusion } from '@/lib/fusion';
      const { data } = useFusion();
    `;
    const script = makeBlock(code);
    const keys = ['name', 'email'];

    const [rewritten, remainingKeys] = injector(code, script, keys);

    expect(remainingKeys).toEqual([]);
    expect(rewritten).toMatchCode(`
      import { useFusion } from "__aliasedFusionPath__";
      const { data } = useFusion([__exportedKeysAsQuotedCsv__], __props.fusion, true);
    `);
  });

  test('handles code with useFusion import and specific keys', () => {
    const code = `
      import { useFusion } from '@/lib/fusion';
      const { data } = useFusion(['name']);
    `;
    const script = makeBlock(code);
    const keys = ['name', 'email'];

    const [rewritten, remainingKeys] = injector(code, script, keys);

    expect(remainingKeys).toEqual(['email']);
    expect(rewritten).toMatchCode(`
      import { useFusion } from "__aliasedFusionPath__";
      const {__exportedKeysAsCsv__} = useFusion([__exportedKeysAsQuotedCsv__], __props.fusion);
      const { data } = useFusion(['name'], __props.fusion, true);
    `);
  });

  test('handles empty array parameter', () => {
    const code = `
      import { useFusion } from '@/lib/fusion';
      const { data } = useFusion([]);
    `;
    const script = makeBlock(code);
    const keys = ['name', 'email'];

    const [rewritten, remainingKeys] = injector(code, script, keys);

    expect(remainingKeys).toEqual(keys);
    expect(rewritten).toMatchCode(`
      import { useFusion } from "__aliasedFusionPath__";
      const {__exportedKeysAsCsv__} = useFusion([__exportedKeysAsQuotedCsv__], __props.fusion);
      const { data } = useFusion([], __props.fusion, true);
    `);
  });

  test('handles whitespace variations', () => {
    const code = `
      import { useFusion } from '@/lib/fusion';
      const { data } = useFusion  (  ['name']  );
    `;
    const script = makeBlock(code);
    const keys = ['name', 'email'];

    const [rewritten, remainingKeys] = injector(code, script, keys);

    expect(remainingKeys).toEqual(['email']);
    expect(rewritten).toMatchCode(`
      import { useFusion } from "__aliasedFusionPath__";
      const {__exportedKeysAsCsv__} = useFusion([__exportedKeysAsQuotedCsv__], __props.fusion);
      const { data } = useFusion  (  ['name'], __props.fusion, true);
    `);
  });

  test('handles useFusion with multiline parameter formatting', () => {
    const code = `
      import { useFusion } from '@/lib/fusion';
      const { data } = useFusion([
        'name',
        'email'
      ]);
    `;
    const script = makeBlock(code);
    const keys = ['name', 'email', 'phone'];

    const [rewritten, remainingKeys] = injector(code, script, keys);

    expect(remainingKeys).toEqual(['phone']);
    expect(rewritten).toMatchCode(`
      import { useFusion } from "__aliasedFusionPath__";
      const { data } = useFusion([
        'name',
        'email'
      ], __props.fusion, true);
    `);
  });

  test('handles useFusion import with other imports', () => {
    const code = `
      import { something } from 'somewhere';
      import { useFusion, otherThing } from '@/lib/fusion';
      import another from 'another-place';
      const { data } = useFusion(['name']);
    `;
    const script = makeBlock(code);
    const keys = ['name', 'email'];

    const [rewritten, remainingKeys] = injector(code, script, keys);

    expect(remainingKeys).toEqual(['email']);
    expect(rewritten).toMatchCode(`
      import { useFusion } from "__aliasedFusionPath__"; 
      import { something } from 'somewhere'; 
      import another from 'another-place'; 
      const {__exportedKeysAsCsv__} = useFusion([__exportedKeysAsQuotedCsv__], __props.fusion); 
      const { data } = useFusion(['name'], __props.fusion, true);
    `);
  });

  test('handles double quoted strings in useFusion parameters', () => {
    const code = `
      import { useFusion } from '@/lib/fusion';
      const { data } = useFusion(["name"]);
    `;
    const script = makeBlock(code);
    const keys = ['name', 'email'];

    const [rewritten, remainingKeys] = injector(code, script, keys);

    expect(remainingKeys).toEqual(['email']);
    expect(rewritten).toMatchCode(`
      import { useFusion } from "__aliasedFusionPath__";
      const {__exportedKeysAsCsv__} = useFusion([__exportedKeysAsQuotedCsv__], __props.fusion);
      const { data } = useFusion(["name"], __props.fusion, true);
    `);
  });

  test('handles mixed quote types in parameters', () => {
    const code = `
      import { useFusion } from '@/lib/fusion';
      const { data } = useFusion(['name', "email"]);
    `;
    const script = makeBlock(code);
    const keys = ['name', 'email', 'phone'];

    const [rewritten, remainingKeys] = injector(code, script, keys);

    expect(remainingKeys).toEqual(['phone']);
    expect(rewritten).toMatchCode(`
      import { useFusion } from "__aliasedFusionPath__";
      const {__exportedKeysAsCsv__} = useFusion([__exportedKeysAsQuotedCsv__], __props.fusion);
      const { data } = useFusion(['name', "email"], __props.fusion, true);
    `);
  });

  test('handles code with no useFusion call or import', () => {
    const code = `
    const x = 42;
  `;
    const script = makeBlock(code);
    const keys = ['a', 'b'];

    const [rewritten, remainingKeys] = injector(code, script, keys);

    expect(remainingKeys).toEqual(keys);
    expect(rewritten).toMatchCode(`
    import { useFusion } from "__aliasedFusionPath__";
    const {__exportedKeysAsCsv__} = useFusion([__exportedKeysAsQuotedCsv__], __props.fusion);
    const x = 42;
  `);
  });

  test('handles useFusion call with trailing comma in parameters', () => {
    const code = `
    import { useFusion } from '@/lib/fusion';
    const data = useFusion(['name',]);
  `;
    const script = makeBlock(code);
    const keys = ['name', 'email'];

    const [rewritten, remainingKeys] = injector(code, script, keys);

    // The trailing comma should be preserved and "name" extracted correctly.
    expect(remainingKeys).toEqual(['email']);
    expect(rewritten).toMatchCode(`
    import { useFusion } from "__aliasedFusionPath__";
    const {__exportedKeysAsCsv__} = useFusion([__exportedKeysAsQuotedCsv__], __props.fusion);
    const data = useFusion(['name',], __props.fusion, true);
  `);
  });

  test('throws error if useFusion is imported with an alias', () => {
    const code = `
      import { useFusion as uf } from '@/lib/fusion';
      const data = uf(['name']);
    `;
    const script = makeBlock(code);
    const keys = ['name', 'email'];

    expect(() => injector(code, script, keys)).toThrow("Aliased useFusion import is not allowed.");
  });

  test('throws error if useFusion is called twice', () => {
    const code = `
    import { useFusion } from '@/lib/fusion';
    const a = useFusion(['name']);
    const b = useFusion(['email']);
  `;
    const script = makeBlock(code);
    const keys = ['name', 'email', 'phone'];

    expect(() => injector(code, script, keys)).toThrow("Multiple useFusion calls are not allowed.");
  });
});