import { Buffer as Buf } from 'buffer';

export const Buffer = Buf;
export const global = window;
export const process = {
    env: {
        NODE_ENV: 'production',
        DEFAULT_FINALITY: 'near-final'
    },
    version: 'v18.0.0',
    nextTick: function(cb) { setTimeout(cb, 0); }
};