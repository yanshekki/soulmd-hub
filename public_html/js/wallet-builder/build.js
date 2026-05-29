require('esbuild').build({
    entryPoints: ['wallet.js'],
    bundle: true,
    minify: true,
    outfile: '../wallet.bundle.js',
    inject: ['./polyfill.js'], // esbuild 會自動抓取 polyfill 裡面的 export 去替換
    alias: {
        crypto: 'crypto-browserify',
        stream: 'stream-browserify',
        util: 'util'
    },
    define: {
        global: 'window',
        'process.env.NODE_ENV': '"production"'
    }
}).then(() => {
    console.log('✅ Web3 Wallet Bundle 編譯及墊片注入成功！');
}).catch((e) => {
    console.error('❌ 編譯失敗：', e);
    process.exit(1);
});