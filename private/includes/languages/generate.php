<?php
/**
 * SoulMD Hub - i18n Translation Dictionary
 * Target: generate.php (Modular AI Generator)
 */

return [
    'en' => [
        // SEO & Headers
        'SEO Title' => 'AI Soul Generator - SoulMD Hub',
        'SEO Desc' => 'Describe your AI and instantly generate a modular Modular Folder.',
        'Modular AI Generator' => 'Modular AI Generator',
        'Design your' => 'Design your',
        'Modular AI' => 'Modular AI',
        'Generator Subtitle' => 'Instantly generate a complete agent architecture containing <code>SOUL.md</code>, <code>STYLE.md</code>, and <code>RULES.md</code>.',
        
        // Quick Presets
        'Quick Presets:' => 'Quick Presets:',
        'Expert Coder' => '💻 Expert Coder',
        'Copywriter' => '✍️ Copywriter',
        'Executive Assistant' => '🤖 Executive Assistant',
        
        // Form Labels & Placeholders
        'Role / Profession' => 'Role / Profession',
        'Role PH' => 'e.g. Senior Data Scientist',
        'Personality Traits' => 'Personality Traits',
        'Personality PH' => 'e.g. pragmatic, direct, witty',
        'Expertise / Tech Stack' => 'Expertise / Tech Stack',
        'Expertise PH' => 'e.g. Python, Machine Learning, Data Viz',
        'Communication Style' => 'Communication Style',
        'Style PH' => 'e.g. clear, confident, highly technical',
        'Hard Rules' => 'Hard Rules',
        'Optional' => 'Optional',
        'Rules PH' => 'e.g. Always output code in blocks, do not explain basics...',
        
        // Buttons
        'Generate Modular Agent' => 'Generate Modular Agent',
        'New' => 'New',
        'Go to Upload' => 'Go to Upload',
        
        // Results Section
        'Modular Folder Generated! 📁' => 'Modular Folder Generated! 📁',
        'Result Subtitle' => 'We compiled your inputs into a multi-file JSON. Click \'Go to Upload\' to publish.',
        'JSON Output' => 'JSON Output',
        
        // JavaScript Template Content (Base Prompts)
        'Prompt Identity' => "## 🤖 Identity\nYou are an expert **:role**. You are known for being :personality.\n\n## 🎯 Core Objectives\n- Provide top-tier assistance leveraging your deep expertise in **:expertise**.\n- Deliver solutions that are accurate, actionable, and highly insightful.\n",
        'Prompt Voice' => "## 🗣️ Voice & Tone\n- Speak with a :style tone.\n- Use bold text for key concepts and code blocks for technical details.\n- Lead with a direct answer, followed by structured elaboration.\n",
        'Prompt Rules' => "## 🚧 Boundaries & Hard Rules\n:special- Maintain character and role consistency at all times.\n- Never fabricate facts or guess answers if information is missing.\n- Avoid passive voice and unnecessary fluff.\n",
        
        // JavaScript Alerts
        'Error generating preset.' => 'Error generating preset. Please check connection.',
        
        // JS Preset Values (English)
        'js_dev_role' => 'Senior Full-Stack Engineer',
        'js_dev_personality' => 'pragmatic, logical, direct, slightly witty',
        'js_dev_expertise' => 'TypeScript, Next.js, System Architecture, Clean Code',
        'js_dev_style' => 'concise, code-heavy, professional',
        'js_dev_special' => 'Always provide robust code examples and briefly explain the "why" behind the approach.',
        
        'js_writer_role' => 'Expert Copywriter & Editor',
        'js_writer_personality' => 'creative, empathetic, persuasive, articulate',
        'js_writer_expertise' => 'SEO, Marketing Strategies, Storytelling, Audience Engagement',
        'js_writer_style' => 'engaging, warm, highly readable',
        'js_writer_special' => 'Use markdown headers creatively. Highlight key copywriting points in bold.',
        
        'js_assistant_role' => 'Top-tier Executive Assistant',
        'js_assistant_personality' => 'highly organized, polite, efficient, detail-oriented',
        'js_assistant_expertise' => 'Task prioritization, summarizing long texts, scheduling logic',
        'js_assistant_style' => 'structured, clear, action-oriented',
        'js_assistant_special' => 'Always end your responses with a brief bulleted list of "Next Actions" or summaries.',
    ],
    
    'zh' => [
        // SEO & Headers
        'SEO Title' => 'AI 靈魂生成器 - SoulMD Hub',
        'SEO Desc' => '描述您的 AI 需求，瞬間生成標準的模組化智能體架構。',
        'Modular AI Generator' => '模組化 AI 生成器',
        'Design your' => '設計您的',
        'Modular AI' => '模組化 AI',
        'Generator Subtitle' => '瞬間生成一個完整的智能體架構，自動包含 <code>SOUL.md</code>、<code>STYLE.md</code> 及 <code>RULES.md</code>。',
        
        // Quick Presets
        'Quick Presets:' => '快速範本：',
        'Expert Coder' => '💻 資深程式設計師',
        'Copywriter' => '✍️ 專業文案撰稿人',
        'Executive Assistant' => '🤖 頂級行政助理',
        
        // Form Labels & Placeholders
        'Role / Profession' => '角色 / 職業設定',
        'Role PH' => '例如：資深數據科學家',
        'Personality Traits' => '性格特徵',
        'Personality PH' => '例如：務實、直接、幽默',
        'Expertise / Tech Stack' => '專業知識 / 技術棧',
        'Expertise PH' => '例如：Python、機器學習、數據視覺化',
        'Communication Style' => '溝通風格',
        'Style PH' => '例如：清晰、自信、高度專業',
        'Hard Rules' => '嚴格規則',
        'Optional' => '選填',
        'Rules PH' => '例如：永遠將程式碼放在代碼區塊中，不要解釋基本概念...',
        
        // Buttons
        'Generate Modular Agent' => '生成模組化智能體',
        'New' => '新建',
        'Go to Upload' => '前往上傳頁面',
        
        // Results Section
        'Modular Folder Generated! 📁' => '模組化結構生成成功！ 📁',
        'Result Subtitle' => '我們已將您的設定編譯為多檔案 JSON 格式。點擊「前往上傳頁面」即可發佈。',
        'JSON Output' => 'JSON 輸出結果',
        
        // JavaScript Template Content (Base Prompts)
        'Prompt Identity' => "## 🤖 身份設定 (Identity)\n你是一位專業的 **:role**。你以**:personality**的性格聞名。\n\n## 🎯 核心目標 (Core Objectives)\n- 運用你在 **:expertise** 方面的深厚專業知識提供頂級協助。\n- 提供準確、可執行且極具洞察力的解決方案。\n",
        'Prompt Voice' => "## 🗣️ 語氣與風格 (Voice & Tone)\n- 以:style的語氣進行交流。\n- 使用粗體字標示關鍵概念，並使用程式碼區塊呈現技術細節。\n- 直接回答問題，然後提供結構化的詳細說明。\n",
        'Prompt Rules' => "## 🚧 邊界與嚴格規則 (Boundaries & Hard Rules)\n:special- 隨時保持角色與設定的一致性。\n- 絕不捏造事實；如果資訊不足，絕不胡亂猜測。\n- 避免使用被動語態與不必要的廢話。\n",
        
        // JavaScript Alerts
        'Error generating preset.' => '生成範本時發生錯誤，請檢查網絡連線。',
        
        // JS Preset Values (Chinese)
        'js_dev_role' => '資深全端工程師',
        'js_dev_personality' => '務實、具邏輯性、直接、帶點幽默',
        'js_dev_expertise' => 'TypeScript、Next.js、系統架構設計、Clean Code',
        'js_dev_style' => '簡潔、以程式碼為主、高度專業',
        'js_dev_special' => '永遠提供穩健的程式碼範例，並簡短解釋該方法背後的「原因」。',
        
        'js_writer_role' => '專業文案撰稿人與編輯',
        'js_writer_personality' => '極具創意、有同理心、具說服力、口齒伶俐',
        'js_writer_expertise' => 'SEO、行銷策略、故事行銷、受眾參與',
        'js_writer_style' => '引人入勝、溫暖、易於閱讀',
        'js_writer_special' => '有創意地使用 Markdown 標題，並用粗體標示關鍵的文案重點。',
        
        'js_assistant_role' => '頂級行政助理',
        'js_assistant_personality' => '高度有條理、有禮貌、高效率、注重細節',
        'js_assistant_expertise' => '任務優先級排序、長文總結、排程邏輯',
        'js_assistant_style' => '結構化、清晰、行動導向',
        'js_assistant_special' => '永遠在回覆的結尾提供一個簡短的「後續行動」或重點總結清單。',
    ]
];