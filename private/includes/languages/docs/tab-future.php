<?php
/**
 * SoulMD Hub - Grand Unified Documentation Sub-module
 * Target: /docs/future (Ecosystem Tokenomics, Financial Ledger & DAO Governance)
 * Layout: Enterprise Deep-Dive AgentFi Tokenomics Whitepaper
 * 🚀 Patched: 100% Parameterized i18n Translation Architecture (No Hardcoded Plain Text).
 * 🚀 Patched: Beautiful Human-Readable JSON structure and separated language file.
 */

// 確保此檔案不被直接存取，必須由 docs.php 引入
if (!defined('BASE_URL')) {
    header("HTTP/1.0 404 Not Found");
    exit;
}
?>

<div class="space-y-12 animate-fade-in text-zinc-300">
    
    <!-- 🧭 頂部總綱 -->
    <div class="border-b border-white/10 pb-6">
        <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight mb-4 leading-tight">
            <?= __('AgentFi_Whitepaper_Title') ?>
        </h2>
        <p class="text-sm sm:text-base leading-relaxed max-w-5xl text-zinc-400">
            <?= __('AgentFi_Whitepaper_Desc') ?>
        </p>
    </div>

    <!-- 📊 模組 1：原生代幣 $SOUL 指數分配與釋放鎖倉矩陣 -->
    <div class="bg-zinc-950/40 border border-purple-500/20 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner hover:border-purple-500/40 transition-all duration-300">
        <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3 select-none">
            <i class="fas fa-chart-pie text-purple-400 bg-purple-500/10 p-2.5 rounded-xl border border-purple-500/20 text-base sm:text-lg"></i>
            <?= __('Tokenomics_Allocation_Title') ?>
        </h3>
        <p class="text-sm text-zinc-400 mb-8 leading-relaxed">
            <?= __('Tokenomics_Allocation_Desc') ?>
        </p>

        <!-- 代幣分配極致可視化表格 -->
        <div class="overflow-x-auto border border-white/10 rounded-2xl bg-zinc-950/50 shadow-2xl">
            <table class="w-full text-left border-collapse font-mono text-sm">
                <thead>
                    <tr class="bg-black/40 border-b border-white/10 text-zinc-400 uppercase tracking-wider text-xs">
                        <th class="p-5 font-bold w-1/4">Allocation Segment</th>
                        <th class="p-5 font-bold w-1/6">Percentage</th>
                        <th class="p-5 font-bold w-1/4">Total Supply</th>
                        <th class="p-5 font-bold">Vesting Schedule & Utility</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-zinc-300">
                    <tr class="hover:bg-purple-500/5 transition-colors">
                        <td class="p-5 font-bold text-emerald-400 flex items-center gap-2"><i class="fas fa-lightbulb"></i> <?= __('Alloc_Creator') ?></td>
                        <td class="p-5 font-black text-white text-lg"><?= __('Alloc_Creator_Pct') ?></td>
                        <td class="p-5 font-bold text-zinc-200"><?= __('Alloc_Creator_Amt') ?></td>
                        <td class="p-5 text-zinc-400 text-xs leading-relaxed"><?= __('Alloc_Creator_Desc') ?></td>
                    </tr>
                    <tr class="hover:bg-purple-500/5 transition-colors bg-black/20">
                        <td class="p-5 font-bold text-purple-400 flex items-center gap-2"><i class="fas fa-university"></i> <?= __('Alloc_Treasury') ?></td>
                        <td class="p-5 font-black text-white text-lg"><?= __('Alloc_Treasury_Pct') ?></td>
                        <td class="p-5 font-bold text-zinc-200"><?= __('Alloc_Treasury_Amt') ?></td>
                        <td class="p-5 text-zinc-400 text-xs leading-relaxed"><?= __('Alloc_Treasury_Desc') ?></td>
                    </tr>
                    <tr class="hover:bg-purple-500/5 transition-colors">
                        <td class="p-5 font-bold text-blue-400 flex items-center gap-2"><i class="fas fa-user-tie"></i> <?= __('Alloc_Investor') ?></td>
                        <td class="p-5 font-black text-white text-lg"><?= __('Alloc_Investor_Pct') ?></td>
                        <td class="p-5 font-bold text-zinc-200"><?= __('Alloc_Investor_Amt') ?></td>
                        <td class="p-5 text-zinc-400 text-xs leading-relaxed"><?= __('Alloc_Investor_Desc') ?></td>
                    </tr>
                    <tr class="hover:bg-purple-500/5 transition-colors bg-black/20">
                        <td class="p-5 font-bold text-amber-400 flex items-center gap-2"><i class="fas fa-coins"></i> <?= __('Alloc_Staking') ?></td>
                        <td class="p-5 font-black text-white text-lg"><?= __('Alloc_Staking_Pct') ?></td>
                        <td class="p-5 font-bold text-zinc-200"><?= __('Alloc_Staking_Amt') ?></td>
                        <td class="p-5 text-zinc-400 text-xs leading-relaxed"><?= __('Alloc_Staking_Desc') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 💎 模組 2：全量回購與代幣強通縮黑洞 -->
    <div class="bg-gradient-to-b from-zinc-950 to-indigo-950/30 border border-indigo-500/20 rounded-3xl p-6 sm:p-8 md:p-10 shadow-2xl relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/10 blur-3xl rounded-full pointer-events-none"></div>
        
        <div class="flex items-center gap-4 mb-6 relative z-10">
            <div class="w-14 h-14 bg-indigo-500/10 border border-indigo-500/30 rounded-2xl flex items-center justify-center text-indigo-400 shrink-0 shadow-lg shadow-indigo-500/10">
                <i class="fas fa-fire-alt text-2xl animate-pulse"></i>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-cyan-400">
                <?= __('AMM_Burn_Title') ?>
            </h3>
        </div>
        
        <p class="text-sm text-zinc-300 mb-8 leading-relaxed font-medium relative z-10">
            <?= __('AMM_Burn_Desc') ?>
        </p>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 relative z-10">
            <div class="bg-black/40 border border-indigo-500/20 p-6 rounded-2xl flex flex-col justify-start hover:border-indigo-400/50 transition-colors">
                <div class="text-indigo-400 mb-4 font-bold text-sm flex items-center gap-2">
                    <i class="fas fa-hammer text-lg"></i> <?= __('Income_Mint') ?>
                </div>
                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed font-mono">
                    <?= __('Income_Mint_Desc') ?>
                </p>
            </div>
            
            <div class="bg-black/40 border border-indigo-500/20 p-6 rounded-2xl flex flex-col justify-start hover:border-indigo-400/50 transition-colors">
                <div class="text-cyan-400 mb-4 font-bold text-sm flex items-center gap-2">
                    <i class="fas fa-exchange-alt text-lg"></i> <?= __('Income_Market') ?>
                </div>
                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed font-mono">
                    <?= __('Income_Market_Desc') ?>
                </p>
            </div>
            
            <div class="bg-black/60 border border-pink-500/40 p-6 rounded-2xl md:transform md:-translate-y-3 relative overflow-hidden flex flex-col justify-start shadow-[0_0_30px_rgba(236,72,153,0.15)] hover:shadow-[0_0_40px_rgba(236,72,153,0.3)] transition-all">
                <div class="absolute inset-0 bg-gradient-to-tr from-pink-500/10 to-transparent pointer-events-none"></div>
                <div class="relative z-10">
                    <div class="text-pink-400 mb-4 font-black text-sm flex items-center gap-2 animate-pulse">
                        <i class="fas fa-sync-alt fa-spin text-lg"></i> <?= __('Income_Burn') ?>
                    </div>
                    <p class="text-xs sm:text-sm text-zinc-200 font-medium leading-relaxed font-mono">
                        <?= __('Income_Burn_Desc') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- 💎 模組 3：人類可觀性 JSON 結構 -->
    <div class="bg-zinc-950/40 border border-white/5 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner hover:border-emerald-500/20 transition-all duration-300 relative overflow-hidden group">
        <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500"></div>
        
        <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3 select-none">
            <i class="fas fa-code text-emerald-400 bg-emerald-500/10 p-2.5 rounded-xl border border-emerald-500/20 text-base sm:text-lg"></i>
            <?= __('NFT_Matrix_Title') ?>
        </h3>
        <p class="text-sm text-zinc-400 mb-8 leading-relaxed"><?= __('NFT_Matrix_Desc') ?></p>
        
        <pre class="bg-[#0d1117] p-5 sm:p-6 rounded-2xl text-xs sm:text-sm font-mono overflow-x-auto border border-white/10 leading-relaxed shadow-2xl">
<span class="text-pink-400">{</span>
  <span class="text-blue-300">"token_id"</span><span class="text-pink-400">:</span> <span class="text-amber-300">"agent-soul-1024"</span><span class="text-zinc-400">,</span>
  <span class="text-blue-300">"owner_id"</span><span class="text-pink-400">:</span> <span class="text-amber-300">"creator_wallet.near"</span><span class="text-zinc-400">,</span>
  <span class="text-zinc-500 italic">// <?= __('JSON_Comment_Metadata') ?></span>
  <span class="text-blue-300">"metadata"</span><span class="text-pink-400">:</span> <span class="text-pink-400">{</span>
    <span class="text-blue-300">"title"</span><span class="text-pink-400">:</span> <span class="text-amber-300">"<?= __('JSON_Value_Title') ?>"</span><span class="text-zinc-400">,</span>
    <span class="text-blue-300">"description"</span><span class="text-pink-400">:</span> <span class="text-amber-300">"<?= __('JSON_Value_Desc') ?>"</span><span class="text-zinc-400">,</span>
    <span class="text-blue-300">"media"</span><span class="text-pink-400">:</span> <span class="text-amber-300">"https://arweave.net/Tx7...pQ2"</span><span class="text-zinc-400">,</span>
    
    <span class="text-zinc-500 italic">// <?= __('JSON_Comment_Extra') ?></span>
    <span class="text-blue-300">"extra"</span><span class="text-pink-400">:</span> <span class="text-pink-400">{</span>
      <span class="text-blue-300">"prompt_hash"</span><span class="text-pink-400">:</span> <span class="text-emerald-300">"sha256:8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86a"</span><span class="text-zinc-400">,</span>
      <span class="text-blue-300">"encryption_salt"</span><span class="text-pink-400">:</span> <span class="text-emerald-300">"0xAB9F2E4D1"</span><span class="text-zinc-400">,</span>
      
      <span class="text-zinc-500 italic">// <?= __('JSON_Comment_Royalty') ?></span>
      <span class="text-blue-300">"royalty_tree"</span><span class="text-pink-400">:</span> <span class="text-pink-400">{</span>
        <span class="text-blue-300">"base_model.near"</span><span class="text-pink-400">:</span> <span class="text-purple-400">500</span><span class="text-zinc-400">,</span>  <span class="text-zinc-500 italic">// 5%</span>
        <span class="text-blue-300">"creator.near"</span><span class="text-pink-400">:</span> <span class="text-purple-400">9500</span>    <span class="text-zinc-500 italic">// 95%</span>
      <span class="text-pink-400">}</span><span class="text-zinc-400">,</span>
      <span class="text-blue-300">"api_endpoint"</span><span class="text-pink-400">:</span> <span class="text-amber-300">"https://api.soulmd-hub.ysk.hk/v1/verify"</span>
    <span class="text-pink-400">}</span>
  <span class="text-pink-400">}</span>
<span class="text-pink-400">}</span></pre>
    </div>

    <!-- 💎 模組 4：去中心化 DAO 代幣治理與可升級合約未來方向 -->
    <div class="bg-zinc-950/40 border border-white/5 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner hover:border-amber-500/20 transition-all duration-300">
        <h3 class="text-xl sm:text-2xl font-bold text-amber-400 mb-4 flex items-center gap-3 select-none">
            <i class="fas fa-users-cog bg-amber-500/10 p-2.5 rounded-xl border border-amber-500/20 text-base sm:text-lg"></i>
            <?= __('DAO_Gov_Title') ?>
        </h3>
        <p class="text-sm text-zinc-400 mb-8 leading-relaxed">
            <?= __('DAO_Gov_Desc') ?>
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl shadow-md hover:border-amber-500/20 transition-colors">
                <h4 class="text-white font-bold text-base mb-3 flex items-center gap-2">
                    <i class="fas fa-sliders-h text-amber-400 text-xs"></i> <?= __('DAO_Prop_1') ?>
                </h4>
                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                    <?= __('DAO_Prop_1_Desc') ?>
                </p>
            </div>
            <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl shadow-md hover:border-amber-500/20 transition-colors">
                <h4 class="text-white font-bold text-base mb-3 flex items-center gap-2">
                    <i class="fas fa-microchip text-amber-400 text-xs"></i> <?= __('DAO_Prop_2') ?>
                </h4>
                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                    <?= __('DAO_Prop_2_Desc') ?>
                </p>
            </div>
        </div>
    </div>

</div>