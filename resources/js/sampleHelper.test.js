import { describe, it, expect } from 'vitest';
import { sampleHelper } from './sampleHelper.js';

describe('sampleHelper', () => {
  it('returns ok', () => {
    expect(sampleHelper()).toBe('ok');
  });
});
