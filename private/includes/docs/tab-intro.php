<?php
/**
 * SoulMD Hub - Grand Unified Documentation Sub-module
 * Target: /docs/intro (Ecosystem Blueprint & Core Engine)
 * 🚀 Patched: Fixed case-sensitive key 'Gateway_Text_Node' for proper i18n rendering.
 */
if (!defined('BASE_URL')) exit;
?>

<div class="space-y-14 animate-fade-in text-zinc-300">
    
    <div class="border-b border-white/10 pb-6">
        <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight mb-4 leading-tight text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-400">
            <?= __('Intro_Master_Title') ?>
        </h2>
        <p class="text-sm sm:text-base leading-relaxed max-w-5xl text-zinc-400">
            <?= __('Intro_Master_Desc') ?>
        </p>
    </div>

    <div class="bg-zinc-950/40 border border-white/5 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner hover:border-emerald-500/20 transition-all duration-300 relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-64 h-64 bg-emerald-500/5 blur-3xl rounded-full pointer-events-none"></div>
        
        <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3 select-none">
            <i class="fas fa-server text-emerald-400 bg-emerald-500/10 p-2.5 rounded-xl border border-emerald-500/20"></i>
            <?= __('Intro_Gateway_Title') ?>
        </h3>
        <p class="text-sm text-zinc-400 mb-8 leading-relaxed"><?= __('Intro_Gateway_Desc') ?></p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-zinc-900/50 border border-white/5 p-6 rounded-2xl shadow-md hover:border-emerald-500/20 transition-colors">
                <h4 class="text-white font-bold text-base mb-3 flex items-center gap-2">
                    <i class="fas fa-font text-emerald-400 text-xs"></i> <?= __('Gateway_Text_Node') ?>
                </h4>
                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                    <?= __('Gateway_Text_Desc') ?>
                </p>
            </div>
            <div class="bg-zinc-900/50 border border-white/5 p-6 rounded-2xl shadow-md hover:border-emerald-500/20 transition-colors">
                <h4 class="text-white font-bold text-base mb-3 flex items-center gap-2">
                    <i class="fas fa-images text-emerald-400 text-xs"></i> <?= __('Gateway_Vision_Node') ?>
                </h4>
                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                    <?= __('Gateway_Vision_Desc') ?>
                </p>
            </div>
        </div>
    </div>

    <div class="bg-zinc-950/40 border border-purple-500/20 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner hover:border-purple-500/40 transition-all duration-300 relative overflow-hidden group">
        <div class="absolute top-0 left-0 w-1 h-full bg-purple-500"></div>
        
        <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3 select-none">
            <i class="fas fa-cubes text-purple-400 bg-purple-500/10 p-2.5 rounded-xl border border-purple-500/20"></i>
            <?= __('Intro_Compiler_Title') ?>
        </h3>
        <p class="text-sm text-zinc-400 mb-8 leading-relaxed"><?= __('Intro_Compiler_Desc') ?></p>
        
        <div class="space-y-4">
            <div class="bg-zinc-900/50 border border-emerald-500/10 p-6 rounded-2xl relative overflow-hidden group hover:border-emerald-500/30 transition-all">
                <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500"></div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                    <h4 class="text-white font-bold text-base flex items-center gap-2">
                        <i class="fas fa-brain text-emerald-400"></i> <?= __('Layer_Soul_Title') ?>
                    </h4>
                    <span class="text-[10px] font-mono text-zinc-500 uppercase tracking-widest bg-zinc-950 px-2 py-0.5 rounded border border-white/5 select-none">Priority 1 (Base Layer)</span>
                </div>
                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                    <?= __('Layer_Soul_Desc') ?>
                </p>
            </div>

            <div class="bg-zinc-900/50 border border-purple-500/10 p-6 rounded-2xl relative overflow-hidden group hover:border-purple-400/30 transition-all">
                <div class="absolute top-0 left-0 w-1 h-full bg-purple-500"></div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                    <h4 class="text-white font-bold text-base flex items-center gap-2">
                        <i class="fas fa-palette text-purple-400"></i> <?= __('Layer_Style_Title') ?>
                    </h4>
                    <span class="text-[10px] font-mono text-zinc-500 uppercase tracking-widest bg-zinc-950 px-2 py-0.5 rounded border border-white/5 select-none">Priority 2 (Voice Matrix)</span>
                </div>
                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                    <?= __('Layer_Style_Desc') ?>
                </p>
            </div>

            <div class="bg-zinc-900/50 border border-red-500/10 p-6 rounded-2xl relative overflow-hidden group hover:border-red-400/30 transition-all">
                <div class="absolute top-0 left-0 w-1 h-full bg-red-500"></div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                    <h4 class="text-white font-bold text-base flex items-center gap-2">
                        <i class="fas fa-exclamation-triangle text-red-400"></i> <?= __('Layer_Rules_Title') ?>
                    </h4>
                    <span class="text-[10px] font-mono text-zinc-500 uppercase tracking-widest bg-zinc-950 px-2 py-0.5 rounded border border-white/5 select-none">Priority 3 (Firewall Rail)</span>
                </div>
                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                    <?= __('Layer_Rules_Desc') ?>
                </p>
            </div>
        </div>
    </div>

    <div class="bg-zinc-950/40 border border-blue-500/20 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner hover:border-blue-500/40 transition-all duration-300 relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-48 h-48 bg-blue-500/5 blur-3xl rounded-full pointer-events-none"></div>
        
        <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3 select-none">
            <i class="fas fa-project-diagram text-blue-400 bg-blue-500/10 p-2.5 rounded-xl border border-blue-500/20"></i>
            <?= __('Intro_Mount_Title') ?>
        </h3>
        <p class="text-sm text-zinc-400 mb-6 leading-relaxed"><?= __('Intro_Mount_Desc') ?></p>

        <div class="bg-[#0d1117] border border-white/10 p-6 rounded-2xl shadow-2xl relative z-10">
            <div class="flex items-center gap-2 border-b border-white/10 pb-3 mb-4 text-cyan-400 font-bold text-sm">
                <i class="fas fa-layer-group"></i> <?= __('Mount_Code_Header') ?>
            </div>
            <pre class="text-xs sm:text-sm font-mono overflow-x-auto leading-relaxed select-all">
<span class="text-zinc-500 italic">// Vanilla Runtime Memory Concatenation Sequence (Left-to-Right Mounting)</span>
<span class="text-purple-400">function</span> <span class="text-emerald-300">compileOperationalSystemFrame</span><span class="text-zinc-400">(</span><span class="text-white">soul_id</span><span class="text-zinc-400">) {</span>
    <span class="text-purple-400">let</span> <span class="text-blue-300">system_frame</span> <span class="text-pink-400">=</span> <span class="text-amber-300">""</span><span class="text-zinc-400">;</span>
    
    <span class="text-zinc-500 italic">// Stacking Priority 1: Base Character Identity Context</span>
    <span class="text-white">system_frame</span> <span class="text-pink-400">+=</span> <span class="text-white">loadProtectedMarkdown</span><span class="text-zinc-400">(</span><span class="text-amber-300">"SOUL.md"</span><span class="text-zinc-400">, </span><span class="text-white">soul_id</span><span class="text-zinc-400">) +</span> <span class="text-amber-300">"\\n\\n"</span><span class="text-zinc-400">;</span>
    
    <span class="text-zinc-500 italic">// Stacking Priority 2: Linguistic Variable Modifiers</span>
    <span class="text-white">system_frame</span> <span class="text-pink-400">+=</span> <span class="text-white">loadProtectedMarkdown</span><span class="text-zinc-400">(</span><span class="text-amber-300">"STYLE.md"</span><span class="text-zinc-400">, </span><span class="text-white">soul_id</span><span class="text-zinc-400">) +</span> <span class="text-amber-300">"\\n\\n"</span><span class="text-zinc-400">;</span>
    
    <span class="text-zinc-500 italic">// Stacking Priority 3: Constraint Security Firewall</span>
    <span class="text-white">system_frame</span> <span class="text-pink-400">+=</span> <span class="text-white">loadProtectedMarkdown</span><span class="text-zinc-400">(</span><span class="text-amber-300">"RULES.md"</span><span class="text-zinc-400">, </span><span class="text-white">soul_id</span><span class="text-zinc-400">);</span>
    
    <span class="text-pink-400">return</span> <span class="text-white">system_frame</span><span class="text-zinc-400">; </span><span class="text-zinc-500 italic">// Atomic payload injected natively into Upstream Runtime</span>
<span class="text-zinc-400">}</span></pre>
        </div>
    </div>

</div>