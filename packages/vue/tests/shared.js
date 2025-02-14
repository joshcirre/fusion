// test-helpers.js
import {expect} from '@jest/globals';

/**
 * Creates a code block object with location information
 * @param {string} code - The source code
 * @returns {Object} Block object with content and location info
 */
export function makeBlock(code) {
  return {
    content: code,
    loc: {start: {offset: 0}, end: {offset: code.length}}
  };
}

/**
 * Custom Jest matcher for comparing code strings while ignoring whitespace differences
 */
export function setupCodeMatcher() {
  expect.extend({
    toMatchCode(received, expected) {
      const normalize = str => str.replace(/\s+/g, ' ').trim();
      const normalizedReceived = normalize(received);
      const normalizedExpected = normalize(expected);
      const pass = normalizedReceived === normalizedExpected;

      if (pass) {
        return {
          message: () =>
            `Expected code not to match:\n` +
            `Expected: ${this.utils.printExpected(normalizedExpected)}\n` +
            `Received: ${this.utils.printReceived(normalizedReceived)}`,
          pass: true
        };
      } else {
        return {
          message: () =>
            `Expected code to match:\n` +
            `Expected: ${this.utils.printExpected(normalizedExpected)}\n` +
            `Received: ${this.utils.printReceived(normalizedReceived)}`,
          pass: false
        };
      }
    }
  });
}