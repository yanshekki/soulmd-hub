<?php
/**
 * SoulMD Hub - Grand Unified Documentation Sub-module
 * Target: /docs/usecases
 * 🚀 Patched: 100% Parameterized i18n Translation Architecture (No Hardcoded Plain Text).
 * 🚀 Patched: Beautiful Institutional Technical Whitepaper Layout with Real Code and Topology Blocks.
 */
if (!defined('BASE_URL')) exit;
?>

<div class="space-y-14 animate-fade-in text-zinc-300">
    
    <div class="border-b border-white/10 pb-6">
        <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight mb-4 leading-tight text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-400">
            <?= __('UseCases_Master_Title') ?>
        </h2>
        <p class="text-sm sm:text-base leading-relaxed max-w-5xl text-zinc-400">
            <?= __('UseCases_Master_Desc') ?>
        </p>
    </div>

    <div class="space-y-10">
        
        <div class="bg-zinc-950/40 border border-emerald-500/20 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner hover:border-emerald-500/40 transition-all duration-300 relative overflow-hidden group">
            <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500"></div>
            <div class="absolute top-0 right-0 w-64 h-64 bg-emerald-500/5 blur-3xl rounded-full pointer-events-none"></div>
            
            <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <i class="fas fa-code-branch text-emerald-400 bg-emerald-500/10 p-2.5 rounded-xl border border-emerald-500/20 text-base"></i>
                <?= __('Usecase1_Title') ?>
            </h3>
            <p class="text-sm text-zinc-400 leading-relaxed mb-6"><?= __('Usecase1_Desc') ?></p>
            
            <div class="bg-[#0d1117] p-5 sm:p-6 rounded-2xl border border-white/10 font-mono text-xs sm:text-sm text-zinc-300 shadow-2xl leading-relaxed">
                <div class="text-white font-bold mb-3 flex items-center gap-2 border-b border-white/5 pb-2">
                    <i class="fas fa-folder-open text-emerald-400"></i> <?= __('Usecase1_Tree_Header') ?>
                </div>
                <ul class="space-y-3 list-none pl-1">
                    <li class="hover:text-emerald-300 transition-colors">📂 <span class="text-zinc-400">workspace/expert-linter-soul/</span></li>
                    <li class="pl-4 border-l border-white/10 py-1 flex flex-col sm:flex-row sm:items-center gap-2">
                        <span class="text-emerald-400 font-bold">├── 📄 SOUL.md</span> 
                        <span class="text-zinc-500 text-xs font-sans"><?= __('Usecase1_Bullet1') ?></span>
                    </li>
                    <li class="pl-4 border-l border-white/10 py-1 flex flex-col sm:flex-row sm:items-center gap-2">
                        <span class="text-purple-400 font-bold">├── 📄 STYLE.md</span> 
                        <span class="text-zinc-500 text-xs font-sans"><?= __('Usecase1_Bullet2') ?></span>
                    </li>
                    <li class="pl-4 border-l border-white/10 py-1 flex flex-col sm:flex-row sm:items-center gap-2">
                        <span class="text-red-400 font-bold">└── 📄 RULES.md</span> 
                        <span class="text-zinc-500 text-xs font-sans"><?= __('Usecase1_Bullet3') ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="bg-zinc-950/40 border border-amber-500/20 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner hover:border-amber-500/40 transition-all duration-300 relative overflow-hidden group">
            <div class="absolute top-0 left-0 w-1 h-full bg-amber-500"></div>
            <div class="absolute top-0 right-0 w-64 h-64 bg-amber-500/5 blur-3xl rounded-full pointer-events-none"></div>
            
            <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <i class="fas fa-network-wired text-amber-400 bg-amber-500/10 p-2.5 rounded-xl border border-amber-500/20 text-base"></i>
                <?= __('Usecase2_Title') ?>
            </h3>
            <p class="text-sm text-zinc-400 leading-relaxed mb-6"><?= __('Usecase2_Desc') ?></p>
            
            <div class="bg-[#0d1117] p-5 sm:p-6 rounded-2xl border border-white/10 font-mono text-xs sm:text-sm text-zinc-300 shadow-2xl leading-relaxed">
                <div class="text-white font-bold mb-3 flex items-center gap-2 border-b border-white/5 pb-2">
                    <i class="fas fa-terminal text-amber-400"></i> <?= __('Usecase2_Payload_Header') ?>
                </div>
                <p class="text-xs text-zinc-500 mb-3 font-sans"><?= __('Usecase2_Payload_Desc') ?></p>
                <pre class="bg-black/40 p-4 rounded-xl text-cyan-300 overflow-x-auto border border-white/5 leading-relaxed select-all">
curl -X POST https://soulmd-hub.ysk.hk/api/chat \
  -H "Authorization: Bearer YOUR_ENCRYPTED_ROTATION_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "action": "chat",
    "soul_id": 1024,
    "session_token": "client_session_nonce_hash",
    "content": "Execute automated system diagnostic optimization routines."
  }'</pre>
            </div>
        </div>

        <div class="bg-zinc-950/40 border border-blue-500/20 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner hover:border-blue-500/40 transition-all duration-300 relative overflow-hidden group">
            <div class="absolute top-0 left-0 w-1 h-full bg-blue-500"></div>
            <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500/5 blur-3xl rounded-full pointer-events-none"></div>
            
            <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <i class="fas fa-file-contract text-blue-400 bg-blue-500/10 p-2.5 rounded-xl border border-blue-500/20 text-base"></i>
                <?= __('Usecase3_Title') ?>
            </h3>
            <p class="text-sm text-zinc-400 leading-relaxed mb-8"><?= __('Usecase3_Desc') ?></p>

            <div class="flex flex-col lg:flex-row gap-6 relative z-10 font-mono text-xs sm:text-sm">
                <div class="flex-1 bg-black/30 border border-white/10 p-5 rounded-2xl">
                    <div class="text-blue-400 font-bold mb-3 flex items-center gap-2 border-b border-white/5 pb-2">
                        <i class="fas fa-project-diagram"></i> <?= __('Usecase3_Flow_Header') ?>
                    </div>
                    <div class="space-y-4 font-sans text-sm text-zinc-400">
                        <div class="flex gap-3">
                            <span class="bg-blue-500/20 text-blue-400 font-mono font-bold text-xs px-2.5 py-1 h-fit rounded border border-blue-500/20">STEP 1</span>
                            <p class="text-xs sm:text-sm leading-relaxed"><?= __('Usecase3_Step1') ?></p>
                        </div>
                        <div class="flex gap-3 border-t border-white/5 pt-3">
                            <span class="bg-purple-500/20 text-purple-400 font-mono font-bold text-xs px-2.5 py-1 h-fit rounded border border-purple-500/20">STEP 2</span>
                            <p class="text-xs sm:text-sm leading-relaxed"><?= __('Usecase3_Step2') ?></p>
                        </div>
                        <div class="flex gap-3 border-t border-white/5 pt-3">
                            <span class="bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs px-2.5 py-1 h-fit rounded border border-emerald-500/20">STEP 3</span>
                            <p class="text-xs sm:text-sm leading-relaxed"><?= __('Usecase3_Step3') ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-zinc-950/40 border border-purple-500/20 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner hover:border-purple-500/40 transition-all duration-300 relative overflow-hidden group">
            <div class="absolute top-0 left-0 w-1 h-full bg-purple-500"></div>
            <div class="absolute top-0 right-0 w-64 h-64 bg-purple-500/5 blur-3xl rounded-full pointer-events-none"></div>
            
            <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <i class="fas fa-compress text-purple-400 bg-purple-500/10 p-2.5 rounded-xl border border-purple-500/20 text-base"></i>
                <?= __('Usecase4_Title') ?>
            </h3>
            <p class="text-sm text-zinc-400 leading-relaxed mb-6"><?= __('Usecase4_Desc') ?></p>
            
            <div class="bg-purple-900/10 border border-purple-500/30 p-5 sm:p-6 rounded-2xl relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-tr from-purple-500/5 to-transparent pointer-events-none"></div>
                <div class="text-purple-400 font-black text-sm mb-3 flex items-center gap-2 border-b border-purple-500/10 pb-2 font-mono tracking-wide">
                    <i class="fas fa-memory animate-pulse"></i> <?= __('Usecase4_Core_Logic') ?>
                </div>
                <p class="text-xs sm:text-sm text-zinc-200 leading-relaxed font-sans">
                    <?= __('Usecase4_Core_Desc') ?>
                </p>
            </div>
        </div>

    </div>
</div>