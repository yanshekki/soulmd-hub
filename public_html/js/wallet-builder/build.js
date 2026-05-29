require('esbuild').build({
    entryPoints: ['wallet.js'],
    bundle: true,
    minify: true,
    outfile: '../wallet.bundle.js',
    alias: {
        crypto: 'crypto-browserify',
        stream: 'stream-browserify',
        util: 'util',
        zlib: 'browserify-zlib'
    },
    define: {
        // 🚨 核彈級替換：將所有隱藏嘅 process 同 Buffer 強制綁定去 window 物件
        global: 'window',
        process: 'window.process',
        Buffer: 'window.Buffer'
    }
}).then(() => {
    console.log('✅ Web3 Wallet Bundle 終極編譯成功！');
}).catch((e) => {
    console.error('❌ 編譯失敗：', e);
    process.exit(1);
});