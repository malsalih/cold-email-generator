<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$e = \App\Models\GeneratedEmail::latest()->first();
if ($e) {
    $variants = $e->generated_variants;
    $all_emails = array_column($e->target_emails, 'email');
    $chunks = array_chunk($all_emails, count($all_emails) / max(1, count($variants)) + 1);
    
    foreach($variants as $k => &$v){
        $v['target_emails'] = $chunks[$k] ?? [$v['target_email']];
    }
    $e->generated_variants = $variants;
    $e->save();
    echo "Fixed latest email ID: " . $e->id . PHP_EOL;
}
