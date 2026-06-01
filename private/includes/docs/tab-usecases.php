<?php
/**
 * SoulMD Hub - Documentation Sub-module
 * Target: /docs?tab=usecases
 * Concept: Daily Use Cases & Production Workflows
 */

// 確保此檔案不被直接存取，必須由 docs.php 引入
if (!defined('BASE_URL')) {
    header("HTTP/1.0 404 Not Found");
    exit;
}
?>

<div class="space-y-8 animate-fade-in">
    
    <div>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-3"><?= __('UseCases Title') ?></h2>
        <p class="text-sm sm:text-base text-zinc-400 leading-relaxed max-w-4xl"><?= __('UseCases Desc') ?></p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8">
        
        <div class="bg-zinc-950/50 border border-white/5 rounded-3xl p-6 sm:p-8 shadow-inner hover:border-emerald-500/30 transition-all duration-300 group flex flex-col relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-500/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            
            <div class="w-12 h-12 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl flex items-center justify-center text-emerald-400 mb-6 group-hover:scale-110 transition duration-300 shrink-0 shadow-lg shadow-emerald-500/5">
                <i class="fas fa-laptop-code text-xl"></i>
            </div>
            
            <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 leading-snug"><?= __('Usecase Coder Title') ?></h3>
            <p class="text-sm text-zinc-400 leading-relaxed flex-grow"><?= __('Usecase Coder Desc') ?></p>
        </div>

        <div class="bg-zinc-950/50 border border-white/5 rounded-3xl p-6 sm:p-8 shadow-inner hover:border-amber-500/30 transition-all duration-300 group flex flex-col relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-amber-500/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            
            <div class="w-12 h-12 bg-amber-500/10 border border-amber-500/20 rounded-2xl flex items-center justify-center text-amber-400 mb-6 group-hover:scale-110 transition duration-300 shrink-0 shadow-lg shadow-amber-500/5">
                <i class="fas fa-network-wired text-xl"></i>
            </div>
            
            <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 leading-snug"><?= __('Usecase Headless Title') ?></h3>
            <p class="text-sm text-zinc-400 leading-relaxed flex-grow"><?= __('Usecase Headless Desc') ?></p>
        </div>

    </div>
</div>