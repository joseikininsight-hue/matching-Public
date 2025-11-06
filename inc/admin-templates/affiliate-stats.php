<?php
/**
 * Affiliate Stats Template
 * アフィリエイト広告統計テンプレート
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap ji-affiliate-admin">
    <h1>広告統計情報</h1>
    <hr class="wp-header-end">
    
    <div class="ji-stats-summary">
        <h2>過去30日間の統計</h2>
        
        <?php if (!empty($stats)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>広告タイトル</th>
                        <th>配置位置</th>
                        <th>表示回数</th>
                        <th>クリック数</th>
                        <th>CTR（%）</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_impressions = 0;
                    $total_clicks = 0;
                    foreach ($stats as $stat): 
                        $total_impressions += $stat->total_impressions;
                        $total_clicks += $stat->total_clicks;
                    ?>
                        <tr>
                            <td><?php echo esc_html($stat->id); ?></td>
                            <td><strong><?php echo esc_html($stat->title); ?></strong></td>
                            <td>
                                <?php 
                                $positions = array(
                                    'sidebar_top' => 'サイドバー上部',
                                    'sidebar_middle' => 'サイドバー中央',
                                    'sidebar_bottom' => 'サイドバー下部',
                                    'content_top' => 'コンテンツ上部',
                                    'content_middle' => 'コンテンツ中央',
                                    'content_bottom' => 'コンテンツ下部'
                                );
                                echo esc_html($positions[$stat->position] ?? $stat->position);
                                ?>
                            </td>
                            <td><?php echo number_format($stat->total_impressions); ?></td>
                            <td><?php echo number_format($stat->total_clicks); ?></td>
                            <td>
                                <strong style="color: <?php echo $stat->ctr >= 2 ? '#00a32a' : '#2c3338'; ?>">
                                    <?php echo number_format($stat->ctr, 2); ?>%
                                </strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f0f0f1; font-weight: bold;">
                        <td colspan="3">合計</td>
                        <td><?php echo number_format($total_impressions); ?></td>
                        <td><?php echo number_format($total_clicks); ?></td>
                        <td>
                            <?php 
                            $overall_ctr = $total_impressions > 0 ? ($total_clicks / $total_impressions) * 100 : 0;
                            echo number_format($overall_ctr, 2); 
                            ?>%
                        </td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="ji-stats-charts" style="margin-top: 40px;">
                <h3>統計グラフ</h3>
                <div class="ji-chart-container" style="background: white; padding: 20px; border: 1px solid #c3c4c7; border-radius: 4px;">
                    <canvas id="ji-stats-chart" width="800" height="300"></canvas>
                </div>
            </div>
            
        <?php else: ?>
            <p>統計データがまだありません。</p>
        <?php endif; ?>
    </div>
</div>

<style>
.ji-stats-summary {
    margin-top: 20px;
}

.ji-stats-charts {
    margin-top: 30px;
}

.ji-chart-container {
    max-width: 1000px;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
jQuery(document).ready(function($) {
    <?php if (!empty($stats)): ?>
    var ctx = document.getElementById('ji-stats-chart').getContext('2d');
    
    var labels = [<?php echo '"' . implode('", "', array_map(function($s) { return $s->title; }, $stats)) . '"'; ?>];
    var impressions = [<?php echo implode(', ', array_map(function($s) { return $s->total_impressions; }, $stats)); ?>];
    var clicks = [<?php echo implode(', ', array_map(function($s) { return $s->total_clicks; }, $stats)); ?>];
    
    var chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: '表示回数',
                    data: impressions,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'クリック数',
                    data: clicks,
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    <?php endif; ?>
});
</script>
