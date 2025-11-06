// AI補助金マッチングアプリ - フロントエンド

const API_BASE_URL = '/api';

// アプリケーションの状態管理
const AppState = {
  sessionId: null,
  currentQuestion: null,
  progress: 0,
  isLoading: false,
  isCompleted: false,
  recommendations: [],
  error: null
};

// ヘルパー関数: 地域表示のフォーマット
function formatLocationDisplay(prefectureName) {
  if (!prefectureName) return '全国';
  
  // 長すぎる場合は省略表示
  if (prefectureName.length > 50) {
    const locations = prefectureName.split(',').map(s => s.trim());
    
    // 東京23区のパターン
    if (locations.length > 10 && locations[0].includes('区')) {
      return '東京都（23区および市部）';
    }
    
    // その他の長いリスト
    return `${locations.slice(0, 3).join('、')}など（${locations.length}地域）`;
  }
  
  return prefectureName;
}

// 初期化
document.addEventListener('DOMContentLoaded', () => {
  initializeApp();
});

async function initializeApp() {
  const appContainer = document.getElementById('app');
  if (!appContainer) return;
  
  showLoading(appContainer);
  
  try {
    const response = await axios.post(`${API_BASE_URL}/sessions`);
    
    if (response.data.success) {
      AppState.sessionId = response.data.data.session_id;
      AppState.currentQuestion = response.data.data.first_question;
      renderQuestion(appContainer);
    } else {
      showError(appContainer, 'セッションの初期化に失敗しました');
    }
  } catch (error) {
    console.error('Initialization error:', error);
    showError(appContainer, 'アプリケーションの起動に失敗しました');
  }
}

// ローディング表示（AI思考中アニメーション付き）
function showLoading(container, message = '読み込み中...') {
  const thinkingMessages = [
    '🤔 あなたの条件を分析中...',
    '💡 最適な補助金を検索中...',
    '🔍 データベースを探索中...',
    '✨ AIがマッチングを計算中...'
  ];
  
  let messageIndex = 0;
  const messageElement = `<p id="loading-message" class="text-lg font-bold">${thinkingMessages[0]}</p>`;
  
  container.innerHTML = `
    <div class="flex flex-col items-center justify-center min-h-[300px]">
      <div class="spinner mb-4"></div>
      ${messageElement}
      <div class="mt-4 flex gap-2">
        <div class="loading-dot" style="animation-delay: 0s"></div>
        <div class="loading-dot" style="animation-delay: 0.2s"></div>
        <div class="loading-dot" style="animation-delay: 0.4s"></div>
      </div>
    </div>
  `;
  
  // メッセージを切り替え
  const interval = setInterval(() => {
    messageIndex = (messageIndex + 1) % thinkingMessages.length;
    const msgEl = document.getElementById('loading-message');
    if (msgEl) {
      msgEl.style.opacity = '0';
      setTimeout(() => {
        msgEl.textContent = thinkingMessages[messageIndex];
        msgEl.style.opacity = '1';
      }, 200);
    } else {
      clearInterval(interval);
    }
  }, 2000);
}

// エラー表示
function showError(container, message) {
  container.innerHTML = `
    <div class="bg-red-50 border-4 border-red-500 p-6 text-center">
      <p class="text-xl font-bold text-red-700 mb-4">⚠️ ${message}</p>
      <button onclick="location.reload()" class="btn-primary">
        再読み込み
      </button>
    </div>
  `;
}

// プログレスバー表示（コンパクト版）
function renderProgressBar(progress) {
  return `
    <div class="mb-4">
      <div class="flex justify-between items-center mb-1">
        <span class="text-sm font-bold">回答進捗</span>
        <span class="text-sm text-mono">${Math.round(progress * 100)}%</span>
      </div>
      <div class="progress-bar">
        <div class="progress-bar-fill" style="width: ${progress * 100}%"></div>
      </div>
    </div>
  `;
}

// 質問のレンダリング（ドロップダウン選択式）
function renderQuestion(container) {
  const question = AppState.currentQuestion;
  const progress = AppState.progress || 0;
  
  let optionsHtml = '';
  
  if (question.type === 'single_select') {
    // ドロップダウン形式に変更
    optionsHtml = `
      <select id="single-select-dropdown" class="w-full p-4 text-lg border-4 border-black font-bold bg-white">
        <option value="">選択してください...</option>
        ${question.options.map(opt => `
          <option value="${opt.value}">${opt.icon || '•'} ${opt.label}</option>
        `).join('')}
      </select>
      <button 
        onclick="submitSingleSelectDropdown()"
        class="btn-primary w-full mt-4"
      >
        次へ進む →
      </button>
    `;
  } else if (question.type === 'multi_select') {
    optionsHtml = `
      <div id="multi-select-options" class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
        ${question.options.map(opt => `
          <button 
            class="question-option text-left flex items-center gap-3"
            data-value="${opt.value}"
            onclick="toggleMultiSelect(this, '${opt.value}')"
          >
            <span class="text-2xl" data-icon="${opt.icon || '•'}">•</span>
            <span class="flex-1 font-medium">${opt.label}</span>
          </button>
        `).join('')}
      </div>
      ${question.allowTextInput ? `
        <input 
          type="text" 
          id="custom-text-input"
          placeholder="${question.textInputPlaceholder || 'その他を入力'}"
          class="mb-4"
        />
      ` : ''}
      <button 
        onclick="submitMultiSelect()"
        class="btn-primary w-full"
      >
        次へ進む →
      </button>
    `;
  } else if (question.type === 'text_input' || question.type === 'long_text') {
    const inputField = question.type === 'long_text' 
      ? `<textarea id="text-input" rows="5" placeholder="${question.placeholder || ''}"></textarea>`
      : `<input type="text" id="text-input" placeholder="${question.placeholder || ''}" />`;
    
    optionsHtml = `
      ${inputField}
      <button 
        onclick="submitTextInput()"
        class="btn-primary w-full mt-4"
      >
        次へ進む →
      </button>
    `;
  }
  
  container.innerHTML = `
    ${renderProgressBar(progress)}
    
    <div class="question-card fade-in">
      <div class="flex items-start gap-3 mb-4">
        <span class="text-3xl">${question.icon || '💡'}</span>
        <div class="flex-1">
          <h2 class="text-xl font-bold mb-1">${question.text}</h2>
          ${question.required ? `
            <span class="inline-block bg-accent-yellow text-black px-2 py-0.5 text-xs font-bold border-2 border-black">
              必須
            </span>
          ` : ''}
        </div>
      </div>
      
      ${optionsHtml}
      
      ${question.skippable ? `
        <button 
          onclick="handleSkip()"
          class="mt-3 text-sm text-gray-500 underline hover:text-black transition-colors"
        >
          この質問をスキップ →
        </button>
      ` : ''}
    </div>
  `;
}

// 単一選択の処理（ドロップダウン対応）
async function submitSingleSelectDropdown() {
  const dropdown = document.getElementById('single-select-dropdown');
  const value = dropdown?.value;
  
  if (!value) {
    alert('選択肢を選んでください');
    return;
  }
  
  await submitAnswer({
    type: 'select',
    value: value
  });
}

// 旧関数（互換性のため残す）
async function handleSingleSelect(value) {
  await submitAnswer({
    type: 'select',
    value: value
  });
}

// 複数選択のトグル
const selectedValues = new Set();

function toggleMultiSelect(button, value) {
  if (selectedValues.has(value)) {
    selectedValues.delete(value);
    button.classList.remove('selected');
    button.querySelector('span[data-icon]').textContent = button.querySelector('span[data-icon]').dataset.icon || '•';
  } else {
    const maxSelections = AppState.currentQuestion.maxSelections || Infinity;
    if (selectedValues.size >= maxSelections) {
      alert(`最大${maxSelections}件まで選択できます`);
      return;
    }
    selectedValues.add(value);
    button.classList.add('selected');
    button.querySelector('span[data-icon]').textContent = '✓';
  }
}

// 複数選択の送信
async function submitMultiSelect() {
  const customText = document.getElementById('custom-text-input')?.value || '';
  
  if (selectedValues.size === 0 && !customText) {
    alert('少なくとも1つ選択してください');
    return;
  }
  
  await submitAnswer({
    type: 'multi_select',
    value: Array.from(selectedValues),
    custom_text: customText || undefined
  });
  
  selectedValues.clear();
}

// テキスト入力の送信
async function submitTextInput() {
  const textInput = document.getElementById('text-input');
  const value = textInput?.value?.trim();
  
  if (!value) {
    if (AppState.currentQuestion.skippable) {
      await handleSkip();
    } else {
      alert('回答を入力してください');
    }
    return;
  }
  
  await submitAnswer({
    type: 'text',
    value: value
  });
}

// スキップ処理
async function handleSkip() {
  await submitAnswer({
    type: 'skip'
  });
}

// 回答送信（AI思考演出付き）
async function submitAnswer(answer) {
  const appContainer = document.getElementById('app');
  showLoading(appContainer, 'AIが分析中...');
  
  try {
    const response = await axios.post(
      `${API_BASE_URL}/sessions/${AppState.sessionId}/answers`,
      {
        question_id: AppState.currentQuestion.id,
        answer: answer
      }
    );
    
    if (response.data.success) {
      if (response.data.data.completed) {
        // マッチング開始
        await fetchRecommendations();
      } else {
        AppState.currentQuestion = response.data.data.next_question;
        AppState.progress = response.data.data.progress;
        renderQuestion(appContainer);
      }
    } else {
      showError(appContainer, response.data.error || '回答の送信に失敗しました');
    }
  } catch (error) {
    console.error('Answer submission error:', error);
    showError(appContainer, '回答の送信中にエラーが発生しました');
  }
}

// 推薦取得（AI思考演出強化）
async function fetchRecommendations() {
  const appContainer = document.getElementById('app');
  showLoading(appContainer, 'AIが最適な補助金を検索中...');
  
  try {
    const response = await axios.get(
      `${API_BASE_URL}/recommendations/${AppState.sessionId}`
    );
    
    if (response.data.success) {
      AppState.recommendations = response.data.data.recommendations;
      AppState.profileSummary = response.data.data.profile_summary;
      AppState.isCompleted = true;
      renderResults(appContainer);
    } else {
      showError(appContainer, response.data.error || '推薦の取得に失敗しました');
    }
  } catch (error) {
    console.error('Recommendations fetch error:', error);
    showError(appContainer, '推薦の取得中にエラーが発生しました');
  }
}

// 結果表示
function renderResults(container) {
  const recommendations = AppState.recommendations;
  const profileSummary = AppState.profileSummary || {};
  
  if (recommendations.length === 0) {
    container.innerHTML = `
      <div class="bg-accent-yellow border-4 border-black p-6 mb-8 brutalist-shadow">
        <h2 class="text-3xl font-bold mb-2">
          😢 条件に合う補助金が見つかりませんでした
        </h2>
        <p class="text-lg mb-4">
          条件を変更してもう一度お試しください。
        </p>
        <button onclick="location.reload()" class="btn-primary">
          最初からやり直す
        </button>
      </div>
    `;
    return;
  }
  
  container.innerHTML = `
    <div class="bg-accent-yellow border-4 border-black p-6 mb-8 brutalist-shadow fade-in">
      <h2 class="text-3xl font-bold mb-2">
        🎉 あなたにおすすめの補助金が見つかりました！
      </h2>
      <p class="text-lg">
        ${recommendations.length}件の補助金をマッチング度順に表示しています
      </p>
    </div>
    
    ${renderProfileSummary(profileSummary)}
    
    <div id="grants-list" class="space-y-6">
      ${recommendations.map((rec, index) => renderGrantCard(rec, index)).join('')}
    </div>
    
    <div class="mt-8 space-y-4">
      <button 
        onclick="location.reload()"
        class="w-full border-2 border-black p-4 font-bold hover:bg-gray-100 transition-colors"
      >
        🔄 最初からやり直す
      </button>
    </div>
    
    <div class="mt-8 p-6 bg-gray-50 border-2 border-gray-300">
      <h3 class="font-bold mb-2">📌 ご注意</h3>
      <ul class="text-sm text-gray-700 space-y-1">
        <li>• 補助金の詳細は必ず公式サイトでご確認ください</li>
        <li>• 申請条件や期限は変更される場合があります</li>
        <li>• 不明点は実施組織へ直接お問い合わせください</li>
      </ul>
    </div>
  `;
}

// プロファイルサマリー表示
function renderProfileSummary(summary) {
  if (!summary || Object.keys(summary).length === 0) return '';
  
  // ラベルマッピング
  const prefectureMap = {
    '01': '北海道', '02': '青森県', '03': '岩手県', '04': '宮城県', '05': '秋田県',
    '06': '山形県', '07': '福島県', '08': '茨城県', '09': '栃木県', '10': '群馬県',
    '11': '埼玉県', '12': '千葉県', '13': '東京都', '14': '神奈川県', '15': '新潟県',
    '16': '富山県', '17': '石川県', '18': '福井県', '19': '山梨県', '20': '長野県',
    '21': '岐阜県', '22': '静岡県', '23': '愛知県', '24': '三重県', '25': '滋賀県',
    '26': '京都府', '27': '大阪府', '28': '兵庫県', '29': '奈良県', '30': '和歌山県',
    '31': '鳥取県', '32': '島根県', '33': '岡山県', '34': '広島県', '35': '山口県',
    '36': '徳島県', '37': '香川県', '38': '愛媛県', '39': '高知県', '40': '福岡県',
    '41': '佐賀県', '42': '長崎県', '43': '熊本県', '44': '大分県', '45': '宮崎県',
    '46': '鹿児島県', '47': '沖縄県'
  };
  
  const purposeMap = {
    'disaster': '災害対策',
    'energy': 'エネルギー・環境',
    'it_dx': 'IT・DX',
    'innovation': 'イノベーション',
    'employment': '雇用・人材育成',
    'regional': '地域活性化',
    'startup': '起業・創業',
    'export': '海外展開'
  };
  
  const amountMap = {
    'under_500k': '50万円未満',
    '500k_1m': '50万円〜100万円',
    '1m_3m': '100万円〜300万円',
    '3m_5m': '300万円〜500万円',
    '5m_10m': '500万円〜1,000万円',
    '10m_30m': '1,000万円〜3,000万円',
    'over_30m': '3,000万円以上',
    'any': '特にこだわらない',
    '100k_500k': '10万円〜50万円'
  };
  
  const deadlineMap = {
    'urgent': '1ヶ月以内',
    '1_3months': '1〜3ヶ月以内',
    '3_6months': '3〜6ヶ月以内',
    '6_12months': '半年〜1年以内',
    'anytime': '期限は問わない'
  };
  
  const items = [];
  if (summary.user_type) items.push({ icon: '👤', label: '種別', value: summary.user_type });
  if (summary.prefecture) {
    const prefName = prefectureMap[summary.prefecture] || summary.prefecture;
    items.push({ icon: '📍', label: '都道府県', value: prefName });
  }
  if (summary.municipality) items.push({ icon: '🏘️', label: '市区町村', value: summary.municipality });
  if (summary.purposes) {
    let purposesText = '';
    if (Array.isArray(summary.purposes)) {
      purposesText = summary.purposes.map(p => purposeMap[p] || p).join('、');
    } else {
      purposesText = purposeMap[summary.purposes] || summary.purposes;
    }
    items.push({ icon: '🎯', label: '目的', value: purposesText });
  }
  if (summary.amount_range) {
    const amountText = amountMap[summary.amount_range] || summary.amount_range;
    items.push({ icon: '💰', label: '希望金額', value: amountText });
  }
  if (summary.deadline) {
    const deadlineText = deadlineMap[summary.deadline] || summary.deadline;
    items.push({ icon: '⏰', label: '期限', value: deadlineText });
  }
  if (summary.ai_message) {
    items.push({ icon: '💬', label: 'AIへの追加要望', value: summary.ai_message });
  }
  
  return `
    <div class="bg-white border-4 border-black p-4 mb-4 brutalist-shadow fade-in">
      <h3 class="text-lg font-bold mb-3 flex items-center gap-2">
        <span>📋</span>
        <span>あなたの回答内容</span>
      </h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        ${items.map(item => `
          <div class="flex items-center gap-2 p-2 bg-gray-50 border-2 border-gray-200 ${item.label === 'AIへの追加要望' ? 'md:col-span-2' : ''}">
            <span class="text-xl">${item.icon}</span>
            <div class="flex-1">
              <div class="text-xs text-gray-600 font-bold">${item.label}</div>
              <div class="text-sm font-bold ${item.label === 'AIへの追加要望' ? 'whitespace-pre-wrap' : ''}">${item.value || '未回答'}</div>
            </div>
          </div>
        `).join('')}
      </div>
    </div>
  `;
}

// 補助金カード表示
function renderGrantCard(rec, index) {
  const grant = rec.grant;
  const rankingBadge = index < 3 ? ['🥇 第1位', '🥈 第2位', '🥉 第3位'][index] : `第${index + 1}位`;
  const badgeColor = index === 0 ? 'bg-accent-yellow' : index === 1 ? 'bg-gray-200' : index === 2 ? 'bg-orange-200' : 'bg-gray-100';
  
  return `
    <div class="grant-card fade-in p-4" style="animation-delay: ${index * 0.1}s">
      <div class="flex justify-between items-start mb-3 gap-3">
        <h3 class="text-lg font-bold flex-1">${grant.title}</h3>
        <div class="flex flex-col items-end gap-1">
          <span class="${badgeColor} px-2 py-0.5 text-xs font-bold border-2 border-black whitespace-nowrap">
            ${rankingBadge}
          </span>
          <span class="bg-accent-green text-black px-2 py-0.5 font-mono text-xs border-2 border-black whitespace-nowrap">
            ${Math.round(rec.matching_score * 100)}% マッチ
          </span>
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-3 text-sm">
        <div class="flex items-center gap-2">
          <span class="text-lg">💰</span>
          <div>
            <span class="text-gray-600">助成金額:</span>
            <span class="ml-1 font-bold">${grant.max_amount_display || '記載なし'}</span>
          </div>
        </div>
        
        <div class="flex items-center gap-2">
          <span class="text-lg">📅</span>
          <div>
            <span class="text-gray-600">申請期限:</span>
            <span class="ml-1 font-bold">${grant.deadline_display || '記載なし'}</span>
          </div>
        </div>
        
        <div class="flex items-center gap-2">
          <span class="text-lg">🏢</span>
          <div>
            <span class="text-gray-600">実施組織:</span>
            <span class="ml-1">${grant.organization || '記載なし'}</span>
          </div>
        </div>
        
        <div class="flex items-center gap-2">
          <span class="text-lg">📍</span>
          <div class="flex-1">
            <span class="text-gray-600">対象地域:</span>
            <span class="ml-1">${formatLocationDisplay(grant.prefecture_name)}</span>
          </div>
        </div>
      </div>
      
      <div class="bg-gray-50 p-3 mb-3 border-l-4 border-accent-green">
        <p class="text-xs font-bold mb-1 flex items-center gap-1">
          <span>🎯</span>
          <span>おすすめ理由</span>
        </p>
        <div id="reasoning-${index}" class="text-xs text-gray-700">
          <p class="whitespace-pre-wrap">${rec.reasoning_summary || rec.reasoning}</p>
          ${rec.reasoning_summary && rec.reasoning_summary !== rec.reasoning ? `
            <button 
              onclick="toggleReasoning(${index})" 
              class="mt-1 text-blue-600 underline hover:text-blue-800 text-xs"
            >
              もっと見る ▼
            </button>
          ` : ''}
        </div>
        <div id="reasoning-full-${index}" class="text-xs text-gray-700 whitespace-pre-wrap hidden">
          ${rec.reasoning}
          <button 
            onclick="toggleReasoning(${index})" 
            class="mt-1 text-blue-600 underline hover:text-blue-800 text-xs"
          >
            閉じる ▲
          </button>
        </div>
      </div>
      
      <div class="flex gap-3">
        <a
          href="${grant.url}"
          target="_blank"
          rel="noopener noreferrer"
          class="flex-1 bg-black text-white py-3 text-center font-bold hover:bg-gray-800 transition-colors border-2 border-black"
        >
          詳細を見る →
        </a>
        
        <button
          onclick="copyToClipboard('${grant.url}')"
          class="px-4 border-2 border-black hover:bg-accent-yellow transition-colors"
          title="URLをコピー"
        >
          📋
        </button>
      </div>
    </div>
  `;
}

// 推薦理由の表示切替
function toggleReasoning(index) {
  const summaryEl = document.getElementById(`reasoning-${index}`);
  const fullEl = document.getElementById(`reasoning-full-${index}`);
  
  if (summaryEl.classList.contains('hidden')) {
    summaryEl.classList.remove('hidden');
    fullEl.classList.add('hidden');
  } else {
    summaryEl.classList.add('hidden');
    fullEl.classList.remove('hidden');
  }
}

// クリップボードにコピー
function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(() => {
    alert('URLをクリップボードにコピーしました!');
  }).catch(err => {
    console.error('Copy failed:', err);
  });
}
