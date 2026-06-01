<?php
/**
 * SoulMD Hub - Documentation Sub-module
 * Target: /docs?tab=solutions
 * Concept: Problems Solved (BYOK Proxy, Canvas Compression, Smart Memory)
 */

// 確保此檔案不被直接存取，必須由 docs.php 引入
if (!defined('BASE_URL')) {
    header("HTTP/1.0 404 Not Found");
    exit;
}
?>

<div class="space-y-8 animate-fade-in">
    
    <div>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-3"><?= __('Solutions Title') ?></h2>
        <p class="text-sm sm:text-base text-zinc-400 leading-relaxed max-w-4xl"><?= __('Solutions Desc') ?></p>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:gap-8">
        
        <div class="bg-zinc-950/50 border border-emerald-500/20 rounded-3xl p-6 sm:p-8 shadow-inner hover:border-emerald-500/40 transition-all duration-300 relative overflow-hidden group">
            <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500"></div>
            <h3 class="text-xl sm:text-2xl font-bold text-white mb-3 flex items-center gap-3">
                <i class="fas fa-user-shield text-emerald-400 bg-emerald-500/10 p-2 rounded-lg"></i> 
                <?= __('BYOK Proxy Engine') ?>
            </h3>
            <p class="text-sm text-zinc-400 mb-6 leading-relaxed"><?= __('BYOK Proxy Desc') ?></p>
            
            <div class="space-y-4">
                <div class="flex items-start gap-4 bg-zinc-900/80 border border-white/5 p-4 sm:p-5 rounded-2xl shadow-sm">
                    <i class="fas fa-lock text-emerald-500 mt-0.5 text-lg shrink-0"></i>
                    <p class="text-sm text-zinc-300 leading-relaxed"><?= __('BYOK Storage') ?></p>
                </div>
                <div class="flex items-start gap-4 bg-zinc-900/80 border border-white/5 p-4 sm:p-5 rounded-2xl shadow-sm">
                    <i class="fas fa-bolt text-emerald-500 mt-0.5 text-lg shrink-0"></i>
                    <p class="text-sm text-zinc-300 leading-relaxed"><?= __('BYOK Flow') ?></p>
                </div>
            </div>
        </div>

        <div class="bg-zinc-950/50 border border-blue-500/20 rounded-3xl p-6 sm:p-8 shadow-inner hover:border-blue-500/40 transition-all duration-300 relative overflow-hidden group">
            <div class="absolute top-0 left-0 w-1 h-full bg-blue-500"></div>
            <h3 class="text-xl sm:text-2xl font-bold text-white mb-3 flex items-center gap-3">
                <i class="fas fa-compress-arrows-alt text-blue-400 bg-blue-500/10 p-2 rounded-lg"></i> 
                <?= __('Timeout Eradication') ?>
            </h3>
            <p class="text-sm text-zinc-400 mb-6 leading-relaxed"><?= __('Timeout Desc') ?></p>
            
            <div class="space-y-4">
                <div class="flex items-start gap-4 bg-zinc-900/80 border border-white/5 p-4 sm:p-5 rounded-2xl shadow-sm">
                    <i class="fas fa-crop-alt text-blue-500 mt-0.5 text-lg shrink-0"></i>
                    <p class="text-sm text-zinc-300 leading-relaxed"><?= __('Canvas Compression') ?></p>
                </div>
                <div class="flex items-start gap-4 bg-zinc-900/80 border border-white/5 p-4 sm:p-5 rounded-2xl shadow-sm">
                    <i class="fas fa-tachometer-alt text-blue-500 mt-0.5 text-lg shrink-0"></i>
                    <p class="text-sm text-zinc-300 leading-relaxed"><?= __('Canvas Result') ?></p>
                </div>
            </div>
        </div>

        <div class="bg-zinc-950/50 border border-purple-500/20 rounded-3xl p-6 sm:p-8 shadow-inner hover:border-purple-500/40 transition-all duration-300 relative overflow-hidden group">
            <div class="absolute top-0 left-0 w-1 h-full bg-purple-500"></div>
            <h3 class="text-xl sm:text-2xl font-bold text-white mb-3 flex items-center gap-3">
                <i class="fas fa-brain text-purple-400 bg-purple-500/10 p-2 rounded-lg"></i> 
                <?= __('Context Bleeping Defense') ?: __('Context Bleeding Defense') ?>
            </h3>
            <p class="text-sm text-zinc-400 mb-6 leading-relaxed">
                <?= __('Context Bleeping Desc') ?: __('Context Bleeding Desc') ?>
            </p>
            
            <div class="space-y-4">
                <div class="flex items-start gap-4 bg-zinc-900/80 border border-white/5 p-4 sm:p-5 rounded-2xl shadow-sm">
                    <i class="fas fa-archive text-purple-500 mt-0.5 text-lg shrink-0"></i>
                    <p class="text-sm text-zinc-300 leading-relaxed"><?= __('Summary Logic') ?></p>
                </div>
            </div>
        </div>

    </div>
</div>