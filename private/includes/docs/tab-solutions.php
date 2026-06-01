<?php
/**
 * SoulMD Hub - Grand Unified Documentation Sub-module
 * Target: /docs/solutions
 * 🚀 Patched: Upgraded to Enterprise Whitepaper Layout with High-Fidelity Pseudo-code Blocks.
 */
if (!defined('BASE_URL')) exit;
?>

<div class="space-y-14 animate-fade-in text-zinc-300">
    
    <div class="border-b border-white/10 pb-6">
        <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight mb-4 leading-tight text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-400">
            <?= __('Solutions_Title') ?>
        </h2>
        <p class="text-sm sm:text-base leading-relaxed max-w-5xl text-zinc-400">
            <?= __('Solutions_Desc') ?>
        </p>
    </div>

    <div class="bg-zinc-950/40 border border-emerald-500/20 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-64 h-64 bg-emerald-500/5 blur-3xl rounded-full pointer-events-none"></div>
        
        <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3 relative z-10">
            <i class="fas fa-key text-emerald-400 bg-emerald-500/10 p-2.5 rounded-xl border border-emerald-500/20"></i>
            <?= __('Sol_BYOK_Title') ?>
        </h3>
        <p class="text-sm text-zinc-400 mb-8 leading-relaxed relative z-10"><?= __('Sol_BYOK_Desc') ?></p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 relative z-10">
            <div class="bg-zinc-900/50 border border-white/5 p-6 rounded-2xl shadow-md hover:border-emerald-500/30 transition-colors">
                <h4 class="text-white font-bold mb-3 flex items-center gap-2">
                    <i class="fas fa-database text-emerald-400 text-sm"></i> <?= __('Sol_BYOK_Storage') ?>
                </h4>
                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed"><?= __('Sol_BYOK_Storage_Desc') ?></p>
            </div>
            
            <div class="bg-zinc-900/50 border border-white/5 p-6 rounded-2xl shadow-md hover:border-emerald-500/30 transition-colors">
                <h4 class="text-white font-bold mb-3 flex items-center gap-2">
                    <i class="fas fa-memory text-emerald-400 text-sm"></i> <?= __('Sol_BYOK_Runtime') ?>
                </h4>
                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed"><?= __('Sol_BYOK_Runtime_Desc') ?></p>
            </div>
        </div>
    </div>

    <div class="bg-zinc-950/40 border border-blue-500/20 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500/5 blur-3xl rounded-full pointer-events-none"></div>
        
        <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3 relative z-10">
            <i class="fas fa-compress-arrows-alt text-blue-400 bg-blue-500/10 p-2.5 rounded-xl border border-blue-500/20"></i>
            <?= __('Sol_Canvas_Title') ?>
        </h3>
        <p class="text-sm text-zinc-400 mb-8 leading-relaxed relative z-10"><?= __('Sol_Canvas_Desc') ?></p>

        <div class="bg-[#0d1117] border border-white/10 p-6 rounded-2xl shadow-2xl relative z-10 mb-6">
            <div class="flex items-center gap-2 border-b border-white/10 pb-3 mb-4 text-blue-400 font-bold text-sm">
                <i class="fas fa-terminal"></i> <?= __('Sol_Canvas_Code_Title') ?>
            </div>
            <p class="text-xs text-zinc-500 mb-4"><?= __('Sol_Canvas_Code_Desc') ?></p>
            <pre class="text-xs sm:text-sm font-mono overflow-x-auto leading-relaxed select-all">
<span class="text-zinc-500 italic">// 1. Initialize Headless HTML5 Canvas (Hardware Accelerated)</span>
<span class="text-purple-400">const</span> <span class="text-blue-300">MAX_DIMENSION</span> <span class="text-pink-400">=</span> <span class="text-amber-300">800</span><span class="text-zinc-400">;</span>
<span class="text-purple-400">const</span> <span class="text-blue-300">QUALITY</span> <span class="text-pink-400">=</span> <span class="text-amber-300">0.6</span><span class="text-zinc-400">;</span>

<span class="text-purple-400">let</span> <span class="text-blue-300">ctx</span> <span class="text-pink-400">=</span> <span class="text-white">canvas</span><span class="text-zinc-400">.</span><span class="text-emerald-300">getContext</span><span class="text-zinc-400">(</span><span class="text-amber-300">'2d'</span><span class="text-zinc-400">);</span>

<span class="text-zinc-500 italic">// 2. Force Bicubic Downscaling inside Client GPU before network payload</span>
<span class="text-blue-300">ctx</span><span class="text-zinc-400">.</span><span class="text-emerald-300">drawImage</span><span class="text-zinc-400">(</span><span class="text-white">rawImage</span><span class="text-zinc-400">, </span><span class="text-amber-300">0</span><span class="text-zinc-400">, </span><span class="text-amber-300">0</span><span class="text-zinc-400">, </span><span class="text-white">targetWidth</span><span class="text-zinc-400">, </span><span class="text-white">targetHeight</span><span class="text-zinc-400">);</span>

<span class="text-zinc-500 italic">// 3. Extract lightweight Base64 string</span>
<span class="text-purple-400">let</span> <span class="text-blue-300">optimizedPayload</span> <span class="text-pink-400">=</span> <span class="text-white">canvas</span><span class="text-zinc-400">.</span><span class="text-emerald-300">toDataURL</span><span class="text-zinc-400">(</span><span class="text-amber-300">'image/jpeg'</span><span class="text-zinc-400">, </span><span class="text-blue-300">QUALITY</span><span class="text-zinc-400">);</span></pre>
        </div>
        
        <div class="bg-blue-900/20 border-l-4 border-l-blue-500 p-4 rounded-r-xl relative z-10">
            <h4 class="text-blue-400 font-bold text-sm mb-1"><?= __('Sol_Canvas_Result') ?></h4>
            <p class="text-xs sm:text-sm text-zinc-300"><?= __('Sol_Canvas_Result_Desc') ?></p>
        </div>
    </div>

    <div class="bg-zinc-950/40 border border-purple-500/20 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-64 h-64 bg-purple-500/5 blur-3xl rounded-full pointer-events-none"></div>
        
        <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3 relative z-10">
            <i class="fas fa-compress text-purple-400 bg-purple-500/10 p-2.5 rounded-xl border border-purple-500/20"></i>
            <?= __('Sol_Memory_Title') ?>
        </h3>
        <p class="text-sm text-zinc-400 mb-8 leading-relaxed relative z-10"><?= __('Sol_Memory_Desc') ?></p>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 relative z-10">
            <div class="bg-zinc-900/60 border border-white/5 p-5 rounded-2xl shadow-lg relative overflow-hidden hover:-translate-y-1 transition-transform">
                <div class="text-purple-400 font-black text-3xl opacity-20 absolute -right-2 -bottom-2">01</div>
                <h4 class="text-white font-bold text-sm mb-2 relative z-10"><?= __('Sol_Memory_Step1') ?></h4>
                <p class="text-xs text-zinc-400 relative z-10"><?= __('Sol_Memory_Step1_Desc') ?></p>
            </div>
            <div class="bg-zinc-900/60 border border-white/5 p-5 rounded-2xl shadow-lg relative overflow-hidden hover:-translate-y-1 transition-transform">
                <div class="text-purple-400 font-black text-3xl opacity-20 absolute -right-2 -bottom-2">02</div>
                <h4 class="text-white font-bold text-sm mb-2 relative z-10"><?= __('Sol_Memory_Step2') ?></h4>
                <p class="text-xs text-zinc-400 relative z-10"><?= __('Sol_Memory_Step2_Desc') ?></p>
            </div>
            <div class="bg-zinc-900/60 border border-white/5 p-5 rounded-2xl shadow-lg relative overflow-hidden hover:-translate-y-1 transition-transform">
                <div class="text-purple-400 font-black text-3xl opacity-20 absolute -right-2 -bottom-2">03</div>
                <h4 class="text-white font-bold text-sm mb-2 relative z-10"><?= __('Sol_Memory_Step3') ?></h4>
                <p class="text-xs text-zinc-400 relative z-10"><?= __('Sol_Memory_Step3_Desc') ?></p>
            </div>
            <div class="bg-zinc-900/60 border border-white/5 p-5 rounded-2xl shadow-lg relative overflow-hidden hover:-translate-y-1 transition-transform border-b-2 border-b-purple-500">
                <div class="text-purple-400 font-black text-3xl opacity-20 absolute -right-2 -bottom-2">04</div>
                <h4 class="text-purple-300 font-bold text-sm mb-2 relative z-10"><?= __('Sol_Memory_Step4') ?></h4>
                <p class="text-xs text-zinc-300 relative z-10"><?= __('Sol_Memory_Step4_Desc') ?></p>
            </div>
        </div>
    </div>

    <div class="bg-zinc-950/40 border border-red-500/20 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-64 h-64 bg-red-500/5 blur-3xl rounded-full pointer-events-none"></div>
        
        <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3 relative z-10">
            <i class="fas fa-fingerprint text-red-400 bg-red-500/10 p-2.5 rounded-xl border border-red-500/20"></i>
            <?= __('Sol_Hash_Title') ?>
        </h3>
        <p class="text-sm text-zinc-400 mb-8 leading-relaxed relative z-10"><?= __('Sol_Hash_Desc') ?></p>

        <div class="bg-[#0d1117] border border-white/10 p-6 rounded-2xl shadow-2xl relative z-10">
            <div class="flex items-center gap-2 border-b border-white/10 pb-3 mb-4 text-red-400 font-bold text-sm">
                <i class="fas fa-link"></i> <?= __('Sol_Hash_Code_Title') ?>
            </div>
            <p class="text-xs text-zinc-500 mb-4"><?= __('Sol_Hash_Code_Desc') ?></p>
            <pre class="text-xs sm:text-sm font-mono overflow-x-auto leading-relaxed select-all">
<span class="text-zinc-500 italic">// Stateless Backend Validation Middleware</span>
<span class="text-purple-400">function</span> <span class="text-emerald-300">verifyAgentIntegrity</span><span class="text-zinc-400">(</span><span class="text-white">db_prompt</span><span class="text-zinc-400">, </span><span class="text-white">db_salt</span><span class="text-zinc-400">, </span><span class="text-white">token_id</span><span class="text-zinc-400">) {</span>
    <span class="text-zinc-500 italic">// 1. Generate hash from local off-chain database</span>
    <span class="text-purple-400">let</span> <span class="text-blue-300">local_hash</span> <span class="text-pink-400">=</span> <span class="text-amber-300">"sha256:"</span> <span class="text-pink-400">+</span> <span class="text-emerald-300">crypto_hash</span><span class="text-zinc-400">(</span><span class="text-white">db_prompt</span> <span class="text-pink-400">+</span> <span class="text-white">db_salt</span><span class="text-zinc-400">);</span>

    <span class="text-zinc-500 italic">// 2. RPC call to NEAR Blockchain Smart Contract (Source of Truth)</span>
    <span class="text-purple-400">let</span> <span class="text-blue-300">on_chain_data</span> <span class="text-pink-400">=</span> <span class="text-emerald-300">near_rpc_call</span><span class="text-zinc-400">(</span><span class="text-amber-300">'get_soul'</span><span class="text-zinc-400">, { </span><span class="text-blue-300">token_id</span><span class="text-zinc-400">: </span><span class="text-white">token_id</span><span class="text-zinc-400"> });</span>
    
    <span class="text-zinc-500 italic">// 3. Circuit Breaker Execution</span>
    <span class="text-pink-400">if</span> <span class="text-zinc-400">(</span><span class="text-blue-300">local_hash</span> <span class="text-pink-400">!==</span> <span class="text-blue-300">on_chain_data</span><span class="text-zinc-400">.</span><span class="text-white">metadata</span><span class="text-zinc-400">.</span><span class="text-white">extra</span><span class="text-zinc-400">) {</span>
        <span class="text-emerald-300">trigger_slashing_penalty</span><span class="text-zinc-400">();</span>
        <span class="text-pink-400">throw new</span> <span class="text-amber-300">Error</span><span class="text-zinc-400">(</span><span class="text-amber-300">"CRITICAL: Prompt tampering detected. Execution blocked."</span><span class="text-zinc-400">);</span>
    <span class="text-zinc-400">}</span>
    <span class="text-pink-400">return</span> <span class="text-amber-300">true</span><span class="text-zinc-400">;</span>
<span class="text-zinc-400">}</span></pre>
        </div>
    </div>

</div>