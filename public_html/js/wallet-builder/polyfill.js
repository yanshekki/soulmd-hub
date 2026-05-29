import { Buffer } from 'buffer';
import process from 'process';

// 確保瀏覽器全域有呢兩個變數
window.Buffer = Buffer;
window.process = process;
window.global = window;

// 🚨 終極關鍵：必須 Export 出來，esbuild 才會在打包時將所有模組的 process 替換成這個！
export { Buffer, process };