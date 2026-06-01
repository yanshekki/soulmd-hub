<?php
/**
 * SoulMD Hub - Documentation Sub-module
 * Target: /docs?tab=future
 * Concept: AgentFi Tokenomics, NFT Ownership & Deflationary Spiral
 */

// 確保此檔案不被直接存取，必須由 docs.php 引入
if (!defined('BASE_URL')) {
    header("HTTP/1.0 404 Not Found");
    exit;
}
?>

<div class="space-y-8 animate-fade-in">
    
    <div>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-3 flex items-center gap-3">
            <?= __('Future Title') ?>
        </h2>
        <p class="text-sm sm:text-base text-zinc-400 leading-relaxed max-w-4xl"><?= __('Future Desc') ?></p>
    </div>

    <div class="space-y-6 sm:space-y-8">
        
        <div class="bg-zinc-950/50 border border-white/5 rounded-3xl p-6 sm:p-8 shadow-inner hover:border-cyan-500/30 transition-all duration-300 flex flex-col md:flex-row gap-6 items-start group">
            <div class="w-14 h-14 bg-cyan-500/10 border border-cyan-500/20 rounded-2xl flex items-center justify-center text-cyan-400 shrink-0 group-hover:scale-110 transition duration-300 shadow-lg shadow-cyan-500/5 mt-1">
                <i class="fas fa-cube text-2xl"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-white mb-3"><?= __('Updatable Agent NFT') ?></h3>
                <p class="text-sm text-zinc-400 leading-relaxed"><?= __('Updatable Agent Desc') ?></p>
            </div>
        </div>

        <div class="bg-zinc-950/50 border border-white/5 rounded-3xl p-6 sm:p-8 shadow-inner hover:border-purple-500/30 transition-all duration-300 flex flex-col md:flex-row gap-6 items-start group">
            <div class="w-14 h-14 bg-purple-500/10 border border-purple-500/20 rounded-2xl flex items-center justify-center text-purple-400 shrink-0 group-hover:scale-110 transition duration-300 shadow-lg shadow-purple-500/5 mt-1">
                <i class="fas fa-handshake text-2xl"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-white mb-3"><?= __('Blackbox Rentals') ?></h3>
                <p class="text-sm text-zinc-400 leading-relaxed"><?= __('Blackbox Rentals Desc') ?></p>
            </div>
        </div>

        <div class="bg-gradient-to-b from-zinc-950 to-amber-950/20 border border-amber-500/20 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-amber-500/10 blur-3xl rounded-full pointer-events-none"></div>
            
            <div class="flex items-center gap-4 mb-6">
                <div class="w-14 h-14 bg-amber-500/10 border border-amber-500/30 rounded-2xl flex items-center justify-center text-amber-400 shrink-0 shadow-lg shadow-amber-500/10">
                    <i class="fas fa-fire-alt text-2xl animate-pulse"></i>
                </div>
                <h3 class="text-xl sm:text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-orange-500">
                    <?= __('Deflationary Spiral Engine') ?>
                </h3>
            </div>
            
            <p class="text-sm text-zinc-300 mb-6 leading-relaxed font-medium">
                <?= __('Deflationary Spiral Desc') ?>
            </p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-black/40 border border-amber-500/10 p-5 rounded-2xl">
                    <div class="text-amber-500 mb-2"><i class="fas fa-hammer"></i></div>
                    <p class="text-xs text-zinc-400 leading-relaxed"><?= __('Spiral Stream 1') ?></p>
                </div>
                <div class="bg-black/40 border border-amber-500/10 p-5 rounded-2xl">
                    <div class="text-orange-500 mb-2"><i class="fas fa-percentage"></i></div>
                    <p class="text-xs text-zinc-400 leading-relaxed"><?= __('Spiral Stream 2') ?></p>
                </div>
                <div class="bg-black/40 border border-red-500/10 p-5 rounded-2xl md:transform md:-translate-y-2 relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-tr from-red-500/5 to-transparent"></div>
                    <div class="text-red-500 mb-2 relative z-10"><i class="fas fa-sync fa-spin"></i></div>
                    <p class="text-xs text-zinc-300 font-medium leading-relaxed relative z-10"><?= __('Spiral Stream 3') ?></p>
                </div>
            </div>
        </div>

    </div>
</div>