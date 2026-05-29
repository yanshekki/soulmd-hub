require('esbuild').build({
    entryPoints: ['wallet.js'],
    bundle: true,
    minify: true,
    outfile: '../wallet.bundle.js',
    inject: ['./node-globals.js'], // 🚨 核心：esbuild 會自動將所有模組找不到的 process 替換成這裡的假 process
    alias: {
        crypto: 'crypto-browserify',
        stream: 'stream-browserify',
        util: 'util',
        buffer: 'buffer'
    }
}).then(() => {
    console.log('✅ 終極 Web3 Wallet Bundle 編譯及墊片注入成功！');
}).catch((e) => {
    console.error('❌ 編譯失敗：', e);
    process.exit(1);
});