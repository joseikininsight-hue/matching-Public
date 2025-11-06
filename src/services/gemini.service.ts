import { GoogleGenerativeAI } from '@google/generative-ai';
import { GeminiInterpretation, QuestionOption, Grant, UserProfile } from '../types';

export class GeminiService {
  private genAI: GoogleGenerativeAI;
  private model: any;

  constructor(apiKey: string) {
    this.genAI = new GoogleGenerativeAI(apiKey);
    this.model = this.genAI.getGenerativeModel({ 
      model: 'gemini-2.0-flash-exp',
      generationConfig: {
        temperature: 0.7,
        topP: 0.95,
        topK: 40,
        maxOutputTokens: 8192,
      }
    });
  }

  // 都道府県コードを都道府県名に変換
  private getPrefectureDisplay(code: string | undefined): string {
    if (!code) return '未回答';
    const prefectures: Record<string, string> = {
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
    return prefectures[code] || code;
  }

  // 自然言語回答の解釈
  async interpretNaturalLanguageAnswer(
    questionId: string,
    questionText: string,
    userAnswer: string,
    options: QuestionOption[]
  ): Promise<GeminiInterpretation> {
    const prompt = `
あなたは補助金マッチングシステムのAIアシスタントです。
ユーザーが自然な言葉で回答した内容を解釈し、最も適切な選択肢を判定してください。

【質問】
${questionText}

【選択肢】
${options.map((opt, i) => `${i + 1}. ${opt.label} (value: ${opt.value})`).join('\n')}

【ユーザーの回答】
${userAnswer}

以下のJSON形式で返答してください:
{
  "matched_options": ["option_value1", "option_value2"],
  "confidence": 0.85,
  "interpretation": "ユーザーの回答の解釈",
  "extracted_keywords": ["キーワード1", "キーワード2"]
}
`;

    const result = await this.model.generateContent(prompt);
    const response = await result.response;
    const text = response.text();
    
    // JSONパース（マークダウンコードブロックを除去）
    const jsonMatch = text.match(/\{[\s\S]*\}/);
    if (!jsonMatch) {
      throw new Error('Invalid JSON response from Gemini');
    }
    
    return JSON.parse(jsonMatch[0]);
  }

  // マッチング推薦理由の生成
  async generateMatchingReasoning(
    userProfile: UserProfile,
    grant: Grant,
    score: number
  ): Promise<string> {
    const q10Message = userProfile.answers['Q010']?.value;
    const userType = userProfile.user_type === 'corporate' ? '法人・企業' : '個人・市民';
    
    const prompt = `
あなたは補助金の専門家です。
以下のユーザープロファイルと補助金情報に基づいて、
なぜこの補助金がユーザーに適しているのか、詳細な推薦理由を生成してください。

【ユーザータイプ】${userType}
${q10Message ? `\n【ユーザーからの追加要望】\n${q10Message}\n` : ''}

【ユーザープロファイル】
- 都道府県: ${userProfile.answers['Q002']?.value || '未回答'}
- 市区町村: ${userProfile.answers['Q003']?.value || '未回答'}
- 目的: ${userProfile.answers['Q004']?.value ? (Array.isArray(userProfile.answers['Q004'].value) ? userProfile.answers['Q004'].value.join(', ') : userProfile.answers['Q004'].value) : '未回答'}
- 希望金額: ${userProfile.answers['Q005']?.value || '未回答'}
- 期限: ${userProfile.answers['Q006']?.value || '未回答'}

【補助金情報】
タイトル: ${grant.title}
対象者: ${grant.grant_target || '記載なし'}
金額: ${grant.max_amount_display || '記載なし'}
期限: ${grant.deadline_display || '記載なし'}
実施組織: ${grant.organization || '記載なし'}
地域: ${grant.prefecture_name || '全国'} ${grant.target_municipality || ''}
カテゴリ: ${grant.categories || '記載なし'}

【マッチングスコア】
${(score * 100).toFixed(1)}%

以下の点を含めて、300〜500文字程度で詳しく説明してください:
1. ユーザータイプ（${userType}）への適合性
2. ${q10Message ? 'ユーザーの追加要望への対応' : 'ユーザーの目的や状況との合致度'}
3. 補助金額や期限の適合性
4. 地域や業種の条件
5. 具体的な活用メリット
6. 申請時の注意点やアドバイス

自然で読みやすい文章で、専門用語は分かりやすく説明してください。
箇条書きで明確に記載してください。
`;

    const result = await this.model.generateContent(prompt);
    const response = await result.response;
    return response.text();
  }

  // 追加質問の生成
  async generateClarificationQuestions(
    userProfile: UserProfile,
    feedbackText: string
  ) {
    const prompt = `
ユーザーが「求めているものと違う」とフィードバックしました。
より正確なマッチングのために、追加で質問すべき内容を3つ生成してください。

【現在のユーザープロファイル】
${JSON.stringify(userProfile, null, 2)}

【ユーザーのフィードバック】
${feedbackText}

以下のJSON形式で返答してください:
{
  "questions": [
    {
      "text": "質問文",
      "type": "single_select",
      "options": [
        {"value": "option1", "label": "選択肢1"},
        {"value": "option2", "label": "選択肢2"}
      ]
    }
  ]
}
`;

    const result = await this.model.generateContent(prompt);
    const response = await result.response;
    const text = response.text();
    
    const jsonMatch = text.match(/\{[\s\S]*\}/);
    if (!jsonMatch) {
      throw new Error('Invalid JSON response from Gemini');
    }
    
    return JSON.parse(jsonMatch[0]);
  }

  // 会話履歴からユーザー意図の抽出
  async extractUserIntent(conversationHistory: any[]) {
    const prompt = `
以下の会話履歴から、ユーザーの真のニーズや優先事項を分析してください。

【会話履歴】
${conversationHistory.map(conv => 
  `Q: ${conv.question_text}\nA: ${conv.answer_value || conv.answer_text || 'スキップ'}`
).join('\n\n')}

以下のJSON形式で返答してください:
{
  "primary_needs": ["主要ニーズ1", "主要ニーズ2"],
  "priorities": {
    "amount": "high",
    "deadline": "medium",
    "location": "low"
  },
  "user_characteristics": ["特徴1", "特徴2"],
  "recommended_focus": "最も重視すべき点"
}
`;

    const result = await this.model.generateContent(prompt);
    const response = await result.response;
    const text = response.text();
    
    const jsonMatch = text.match(/\{[\s\S]*\}/);
    if (!jsonMatch) {
      throw new Error('Invalid JSON response from Gemini');
    }
    
    return JSON.parse(jsonMatch[0]);
  }

  // 補助金の類似度計算（テキストベース）
  async calculateSimilarity(userProfileText: string, grantText: string): Promise<number> {
    const prompt = `
ユーザーのプロファイルと補助金の説明文を比較し、マッチング度を0〜1の数値で評価してください。

【ユーザープロファイル】
${userProfileText}

【補助金情報】
${grantText}

以下のJSON形式で返答してください:
{
  "score": 0.85,
  "reasoning": "スコアの根拠"
}
`;

    const result = await this.model.generateContent(prompt);
    const response = await result.response;
    const text = response.text();
    
    const jsonMatch = text.match(/\{[\s\S]*\}/);
    if (!jsonMatch) {
      return 0.5; // デフォルト値
    }
    
    const parsed = JSON.parse(jsonMatch[0]);
    return parsed.score || 0.5;
  }

  // バッチでの推薦ランキング生成
  async generateBatchRanking(
    userProfile: UserProfile,
    candidates: Grant[],
    topK: number = 10
  ) {
    // 回答値を安全に抽出するヘルパー関数
    const extractValue = (answer: any): any => {
      if (!answer) return null;
      if (answer.value !== undefined) {
        // answer.value がさらにオブジェクトの場合
        if (answer.value && typeof answer.value === 'object' && answer.value.value !== undefined) {
          return answer.value.value;
        }
        return answer.value;
      }
      return answer;
    };
    
    // Q10のメッセージを抽出
    const q10Message = extractValue(userProfile.answers['Q010']);
    const userType = userProfile.user_type === 'corporate' ? '法人・企業' : '個人・市民';
    const prefectureCode = extractValue(userProfile.answers['Q002']);
    const municipality = extractValue(userProfile.answers['Q003']);
    const purposes = extractValue(userProfile.answers['Q004']);
    const amountRange = extractValue(userProfile.answers['Q005']);
    const deadline = extractValue(userProfile.answers['Q006']);
    
    const prompt = `
あなたは補助金マッチングの専門家です。
以下のユーザープロファイルに最も適した補助金を、上位${topK}件選定してランキングしてください。

【重要】ユーザータイプ: ${userType}
${q10Message ? `\n【最優先考慮事項】ユーザーからの追加要望:\n${q10Message}\n` : ''}

【ユーザープロファイル】
- ユーザータイプ: ${userType}
- 都道府県: ${this.getPrefectureDisplay(prefectureCode)}
- 市区町村: ${municipality || '未回答'}
- 目的: ${purposes ? (Array.isArray(purposes) ? purposes.join(', ') : purposes) : '未回答'}
- 希望金額: ${amountRange || '未回答'}
- 期限: ${deadline || '未回答'}
${userProfile.extracted_intent ? `- 抽出されたニーズ: ${userProfile.extracted_intent.primary_needs.join(', ')}` : ''}

【候補補助金（${candidates.length}件中、上位20件を表示）】
${candidates.slice(0, 20).map((grant, i) => `
[${i + 1}] ID: ${grant.id}
タイトル: ${grant.title}
対象者: ${grant.grant_target || '記載なし'}
金額: ${grant.max_amount_display || '記載なし'}
期限: ${grant.deadline_display || '記載なし'}
地域: ${grant.prefecture_name || '全国'} ${grant.target_municipality || ''}
カテゴリ: ${grant.categories || '記載なし'}
`).join('\n')}

【ランキング基準の絶対優先順位】
**最優先1: 地域の完全一致**
${municipality ? `- 市区町村「${municipality}」が地域欄に含まれる補助金を **最優先**（1位～から配置）` : ''}
- 都道府県「${this.getPrefectureDisplay(prefectureCode)}」が含まれる補助金を **第2優先**
- 地域欄が「全国」の補助金は **最後** に配置（地域限定の補助金がない場合のみ）

**優先2: ユーザーの追加要望**
${q10Message ? `- Q10メッセージ「${q10Message}」に合致する補助金を優先` : '- Q10メッセージなし'}

**優先3: ユーザータイプ**
- ユーザータイプ（${userType}）への適合性

**優先4: 目的・カテゴリ**
- 地域が一致する補助金の中で、カテゴリ・目的の一致度で順位決定

**優先5: 金額・期限**
- 希望金額・期限への適合性

**【絶対に守るべきルール】**
1. 市区町村が一致する補助金 > 都道府県のみ一致 > 全国対応
2. 全国対応の補助金は、地域限定の補助金より必ず下位
3. 地域が同じ場合のみ、他の条件で順位付け

以下のJSON形式で返答してください:
{
  "rankings": [
    {
      "grant_id": 123,
      "rank": 1,
      "score": 0.95,
      "reasoning_summary": "簡潔な推薦理由（50文字程度）"
    }
  ]
}
`;

    const result = await this.model.generateContent(prompt);
    const response = await result.response;
    const text = response.text();
    
    console.log('Gemini response text (first 500 chars):', text.substring(0, 500));
    
    // Try to find JSON in various formats (with or without markdown code blocks)
    let jsonText = text;
    
    // Remove markdown code blocks if present
    const codeBlockMatch = text.match(/```(?:json)?\s*([\s\S]*?)```/);
    if (codeBlockMatch) {
      jsonText = codeBlockMatch[1].trim();
    }
    
    // Try to extract JSON object
    const jsonMatch = jsonText.match(/\{[\s\S]*\}/);
    if (!jsonMatch) {
      console.error('Failed to parse JSON from Gemini response:', text);
      throw new Error('Invalid JSON response from Gemini');
    }
    
    try {
      const parsed = JSON.parse(jsonMatch[0]);
      
      // Validate structure
      if (!parsed.rankings || !Array.isArray(parsed.rankings)) {
        console.error('Invalid rankings structure:', parsed);
        throw new Error('Gemini response missing rankings array');
      }
      
      // Validate each ranking has required fields
      const validRankings = parsed.rankings.filter((r: any) => 
        r && typeof r.grant_id === 'number' && typeof r.rank === 'number'
      );
      
      console.log(`Successfully parsed ${validRankings.length}/${parsed.rankings.length} valid Gemini rankings`);
      
      return { rankings: validRankings };
    } catch (parseError) {
      console.error('JSON parse error:', parseError);
      console.error('JSON string (first 500 chars):', jsonMatch[0].substring(0, 500));
      throw new Error('Invalid JSON response from Gemini');
    }
  }
}
