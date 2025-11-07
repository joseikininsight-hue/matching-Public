<?php
/**
 * WordPressに追加するコード
 * 
 * ACFフィールドをREST APIで公開するための設定
 * 
 * このコードを以下のいずれかの場所に追加してください：
 * 1. テーマのfunctions.php（推奨）
 * 2. wp-config.phpの前（上部）
 * 3. Must-Useプラグイン（/wp-content/mu-plugins/acf-rest-api.php）
 */

// ========================================
// 方法1: すべてのACFフィールドをREST APIで公開（最も簡単）
// ========================================

add_filter('acf/settings/rest_api_enabled', '__return_true');


// ========================================
// 方法2: 特定のフィールドグループのみREST APIで公開（より安全）
// ========================================

add_filter('acf/rest_api/group_grant_details/get_fields', function($data, $request, $post_object) {
    // 助成金詳細フィールドをREST APIで返す
    return $data;
}, 10, 3);


// ========================================
// 方法3: カスタム投稿タイプ「grant」のACFフィールドをREST APIで公開
// ========================================

add_action('rest_api_init', function() {
    // grantカスタム投稿タイプのメタフィールドをREST APIで公開
    register_rest_field('grant', 'acf', array(
        'get_callback' => function($post) {
            $fields = get_fields($post['id']);
            return $fields ? $fields : [];
        },
        'schema' => array(
            'description' => 'ACF Fields',
            'type' => 'object'
        ),
    ));
});


// ========================================
// 方法4: 個別フィールドをREST APIで公開（最も柔軟）
// ========================================

add_action('rest_api_init', function() {
    $acf_fields = array(
        'organization',
        'max_amount',
        'max_amount_numeric',
        'deadline',
        'deadline_date',
        'official_url',
        'grant_target',
        'eligible_expenses',
        'required_documents',
        'application_status',
        'adoption_rate',
        'difficulty_level',
        'area_notes',
        'subsidy_rate_detailed',
        'organization_type',
        'min_amount',
        'application_period',
        'eligible_expenses_detailed',
        'regional_limitation',
        'application_method',
        'contact_info',
        'external_link',
        'is_featured',
        'priority_order',
    );
    
    foreach ($acf_fields as $field_name) {
        register_rest_field('grant', $field_name, array(
            'get_callback' => function($post) use ($field_name) {
                return get_field($field_name, $post['id']);
            },
            'schema' => array(
                'description' => ucfirst(str_replace('_', ' ', $field_name)),
                'type' => 'string'
            ),
        ));
    }
});


// ========================================
// 推奨: 方法1と方法3を組み合わせる（最も確実）
// ========================================

// ステップ1: ACF REST APIを全体的に有効化
add_filter('acf/settings/rest_api_enabled', '__return_true');

// ステップ2: grantカスタム投稿タイプで確実にACFフィールドを返す
add_action('rest_api_init', function() {
    register_rest_field('grant', 'acf', array(
        'get_callback' => function($post) {
            $fields = get_fields($post['id']);
            
            // 空の場合は空の配列ではなくオブジェクトを返す
            if (empty($fields) || !is_array($fields)) {
                return new stdClass();
            }
            
            return $fields;
        },
        'update_callback' => null,
        'schema' => array(
            'description' => 'Advanced Custom Fields',
            'type' => 'object',
        ),
    ));
});

// ステップ3: デバッグ用 - REST APIレスポンスにACFフィールドが含まれているか確認
add_filter('rest_prepare_grant', function($response, $post, $request) {
    $fields = get_fields($post->ID);
    
    if ($fields && is_array($fields)) {
        $response->data['acf_fields_count'] = count($fields);
        $response->data['acf_field_names'] = array_keys($fields);
    } else {
        $response->data['acf_debug'] = 'No ACF fields found';
    }
    
    return $response;
}, 10, 3);


// ========================================
// トラブルシューティング用関数
// ========================================

/**
 * ACFフィールドが正しく取得できるかテストする
 * 
 * 使用方法: URLに ?test_acf=1 を追加してアクセス
 * 例: https://joseikin-insight.com/wp-json/wp/v2/grants/130564?test_acf=1
 */
add_action('init', function() {
    if (isset($_GET['test_acf']) && current_user_can('manage_options')) {
        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 130564;
        
        echo '<h1>ACF Fields Test</h1>';
        echo '<h2>Post ID: ' . $post_id . '</h2>';
        
        $fields = get_fields($post_id);
        
        echo '<h3>ACF Fields:</h3>';
        echo '<pre>';
        print_r($fields);
        echo '</pre>';
        
        echo '<h3>Individual Field Test:</h3>';
        echo 'Organization: ' . get_field('organization', $post_id) . '<br>';
        echo 'Max Amount: ' . get_field('max_amount', $post_id) . '<br>';
        echo 'Deadline: ' . get_field('deadline', $post_id) . '<br>';
        echo 'Official URL: ' . get_field('official_url', $post_id) . '<br>';
        
        exit;
    }
});
