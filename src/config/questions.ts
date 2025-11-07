import { Question } from '../types';

// 都道府県リスト
export const prefectures = [
  { value: '01', label: '北海道' },
  { value: '02', label: '青森県' },
  { value: '03', label: '岩手県' },
  { value: '04', label: '宮城県' },
  { value: '05', label: '秋田県' },
  { value: '06', label: '山形県' },
  { value: '07', label: '福島県' },
  { value: '08', label: '茨城県' },
  { value: '09', label: '栃木県' },
  { value: '10', label: '群馬県' },
  { value: '11', label: '埼玉県' },
  { value: '12', label: '千葉県' },
  { value: '13', label: '東京都' },
  { value: '14', label: '神奈川県' },
  { value: '15', label: '新潟県' },
  { value: '16', label: '富山県' },
  { value: '17', label: '石川県' },
  { value: '18', label: '福井県' },
  { value: '19', label: '山梨県' },
  { value: '20', label: '長野県' },
  { value: '21', label: '岐阜県' },
  { value: '22', label: '静岡県' },
  { value: '23', label: '愛知県' },
  { value: '24', label: '三重県' },
  { value: '25', label: '滋賀県' },
  { value: '26', label: '京都府' },
  { value: '27', label: '大阪府' },
  { value: '28', label: '兵庫県' },
  { value: '29', label: '奈良県' },
  { value: '30', label: '和歌山県' },
  { value: '31', label: '鳥取県' },
  { value: '32', label: '島根県' },
  { value: '33', label: '岡山県' },
  { value: '34', label: '広島県' },
  { value: '35', label: '山口県' },
  { value: '36', label: '徳島県' },
  { value: '37', label: '香川県' },
  { value: '38', label: '愛媛県' },
  { value: '39', label: '高知県' },
  { value: '40', label: '福岡県' },
  { value: '41', label: '佐賀県' },
  { value: '42', label: '長崎県' },
  { value: '43', label: '熊本県' },
  { value: '44', label: '大分県' },
  { value: '45', label: '宮崎県' },
  { value: '46', label: '鹿児島県' },
  { value: '47', label: '沖縄県' }
];

// 基本質問（必須）
export const baseQuestions: Question[] = [
  {
    id: 'Q001',
    category: 'basic',
    text: 'あなたは企業ですか、それとも個人ですか？',
    type: 'single_select',
    icon: '👤',
    options: [
      { value: 'corporate', label: '企業・法人', icon: '🏢' },
      { value: 'individual', label: '個人・市民', icon: '👤' }
    ],
    required: true,
    weight: 1.0
  },
  {
    id: 'Q002',
    text: '事業所・お住まいの都道府県を教えてください',
    type: 'single_select',
    icon: '📍',
    options: 'prefectures',
    required: true,
    weight: 0.9
  },
  {
    id: 'Q003',
    text: '市区町村名を入力してください（任意）',
    type: 'text_input',
    icon: '🏘️',
    placeholder: '例：新宿区、横浜市、箕輪町',
    skippable: true,
    weight: 0.5
  },
  {
    id: 'Q004',
    text: '補助金の使用目的を教えてください（複数選択可）',
    type: 'multi_select',
    icon: '🎯',
    options: 'categories',
    maxSelections: 5,
    allowTextInput: true,
    textInputPlaceholder: 'その他の目的を入力',
    required: true,
    weight: 1.0
  },

  {
    id: 'Q006',
    text: '申請期限の希望はありますか？',
    type: 'single_select',
    icon: '📅',
    options: [
      { value: 'urgent', label: '1ヶ月以内に申請したい' },
      { value: '1_3months', label: '1〜3ヶ月以内' },
      { value: '3_6months', label: '3〜6ヶ月以内' },
      { value: '6_12months', label: '半年〜1年以内' },
      { value: 'anytime', label: '期限は問わない' }
    ],
    skippable: true,
    weight: 0.6
  },
  {
    id: 'Q010',
    text: 'AIに伝えたいこと（任意）',
    type: 'long_text',
    icon: '💬',
    placeholder: 'その他、AIに伝えたい追加情報があれば自由にご記入ください。\n例：特定の条件、優先したいこと、懸念事項など',
    skippable: true,
    weight: 0.8
  }
];

// 企業向け追加質問
export const corporateQuestions: Question[] = [
  {
    id: 'Q101',
    text: '貴社の業種を教えてください（複数選択可）',
    type: 'multi_select',
    icon: '🏭',
    options: [
      { value: 'manufacturing', label: '製造業' },
      { value: 'it', label: '情報通信業' },
      { value: 'agriculture', label: '農業・林業・漁業' },
      { value: 'construction', label: '建設業' },
      { value: 'retail', label: '小売業' },
      { value: 'wholesale', label: '卸売業' },
      { value: 'service', label: 'サービス業' },
      { value: 'hospitality', label: '宿泊・飲食サービス業' },
      { value: 'transport', label: '運輸業' },
      { value: 'medical', label: '医療・福祉' },
      { value: 'education', label: '教育・学習支援業' },
      { value: 'other', label: 'その他' }
    ],
    maxSelections: 3,
    allowTextInput: true,
    weight: 0.8
  },
  {
    id: 'Q102',
    text: '従業員数を教えてください',
    type: 'single_select',
    icon: '👥',
    options: [
      { value: 'micro', label: '5人以下' },
      { value: 'small1', label: '6〜20人' },
      { value: 'small2', label: '21〜50人' },
      { value: 'medium1', label: '51〜100人' },
      { value: 'medium2', label: '101〜300人' },
      { value: 'large', label: '301人以上' }
    ],
    skippable: true,
    weight: 0.5
  },
  {
    id: 'Q103',
    text: '年間売上高を教えてください',
    type: 'single_select',
    icon: '💹',
    options: [
      { value: 'under_10m', label: '1,000万円未満' },
      { value: '10m_50m', label: '1,000万円〜5,000万円' },
      { value: '50m_100m', label: '5,000万円〜1億円' },
      { value: '100m_500m', label: '1億円〜5億円' },
      { value: '500m_1b', label: '5億円〜10億円' },
      { value: 'over_1b', label: '10億円以上' },
      { value: 'prefer_not', label: '回答しない' }
    ],
    skippable: true,
    weight: 0.4
  },
  {
    id: 'Q104',
    text: '創業からの年数を教えてください',
    type: 'single_select',
    icon: '📆',
    options: [
      { value: 'startup', label: '創業前・準備中' },
      { value: 'under_1y', label: '1年未満' },
      { value: '1_3y', label: '1〜3年' },
      { value: '3_5y', label: '3〜5年' },
      { value: '5_10y', label: '5〜10年' },
      { value: 'over_10y', label: '10年以上' }
    ],
    skippable: true,
    weight: 0.6
  }
];

// 個人向け追加質問
export const individualQuestions: Question[] = [
  {
    id: 'Q201',
    text: '現在の状況を教えてください',
    type: 'single_select',
    icon: '💼',
    options: [
      { value: 'employed', label: '会社員・公務員' },
      { value: 'self_employed', label: '個人事業主・フリーランス' },
      { value: 'startup_planning', label: '起業準備中' },
      { value: 'student', label: '学生' },
      { value: 'unemployed', label: '求職中' },
      { value: 'homemaker', label: '主婦・主夫' },
      { value: 'retired', label: '退職・年金生活' },
      { value: 'other', label: 'その他' }
    ],
    weight: 0.7
  },
  {
    id: 'Q202',
    text: '年齢層を教えてください',
    type: 'single_select',
    icon: '🎂',
    options: [
      { value: 'under_20', label: '20歳未満' },
      { value: '20_29', label: '20〜29歳' },
      { value: '30_39', label: '30〜39歳' },
      { value: '40_49', label: '40〜49歳' },
      { value: '50_59', label: '50〜59歳' },
      { value: '60_69', label: '60〜69歳' },
      { value: 'over_70', label: '70歳以上' }
    ],
    skippable: true,
    weight: 0.5
  },
  {
    id: 'Q203',
    text: '世帯構成を教えてください',
    type: 'single_select',
    icon: '👨‍👩‍👧‍👦',
    options: [
      { value: 'single', label: '単身' },
      { value: 'couple', label: '夫婦のみ' },
      { value: 'couple_children', label: '夫婦と子供' },
      { value: 'single_parent', label: 'ひとり親世帯' },
      { value: 'extended', label: '三世代同居' },
      { value: 'other', label: 'その他' }
    ],
    skippable: true,
    weight: 0.6
  }
];

// 詳細絞り込み質問（オプション）
export const detailedQuestions: Question[] = [
  {
    id: 'Q301',
    text: '具体的にどのような設備・システムを導入予定ですか？',
    type: 'long_text',
    icon: '🔧',
    placeholder: '例：生産ラインの自動化設備、クラウド会計システム、太陽光発電設備など、できるだけ具体的にご記入ください',
    skippable: true,
    weight: 0.8
  },
  {
    id: 'Q302',
    text: '補助金を活用して達成したい目標を教えてください',
    type: 'long_text',
    icon: '🎯',
    placeholder: '例：生産性を30%向上させたい、CO2排出量を50%削減したい、新規顧客を100社獲得したいなど',
    skippable: true,
    weight: 0.7
  },
  {
    id: 'Q303',
    text: '過去に補助金・助成金の申請経験はありますか？',
    type: 'single_select',
    icon: '📝',
    options: [
      { value: 'none', label: 'ない' },
      { value: 'applied', label: 'ある（申請のみ）' },
      { value: 'received', label: 'ある（採択・受給済み）' }
    ],
    skippable: true,
    weight: 0.3
  },
  {
    id: 'Q304',
    text: '申請書類の作成サポートは必要ですか？',
    type: 'single_select',
    icon: '📋',
    options: [
      { value: 'self', label: '自分で作成できる' },
      { value: 'partial', label: '一部サポートが欲しい' },
      { value: 'full', label: '全面的なサポートが必要' }
    ],
    skippable: true,
    weight: 0.2
  },
  {
    id: 'Q305',
    text: 'その他、補助金に関する要望や条件があれば自由に入力してください',
    type: 'long_text',
    icon: '💭',
    placeholder: '例：オンライン申請可能なものが良い、地元企業との連携が条件でも可、事前着手可能なものを希望など',
    skippable: true,
    weight: 0.5
  }
];

// すべての質問を取得する関数
export function getAllQuestions(): Question[] {
  return [
    ...baseQuestions,
    ...corporateQuestions,
    ...individualQuestions,
    ...detailedQuestions
  ];
}

// IDで質問を取得
export function getQuestionById(id: string): Question | undefined {
  return getAllQuestions().find(q => q.id === id);
}

// ユーザータイプに応じた質問リストを取得
export function getQuestionsForUserType(userType?: 'corporate' | 'individual'): Question[] {
  const questions = [...baseQuestions];
  
  if (userType === 'corporate') {
    questions.push(...corporateQuestions);
  } else if (userType === 'individual') {
    questions.push(...individualQuestions);
  }
  
  return questions;
}
