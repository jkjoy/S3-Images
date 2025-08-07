<?php
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// 配置 S3 客户端
$s3Client = new S3Client([
    'version'     => 'latest',
    'region'      => getenv('S3_REGION') ?: 'us-east-1', // 从环境变量读取，设置默认值
    'endpoint'    => getenv('S3_ENDPOINT') ?: 'https://s3.bitiful.net', // 从环境变量读取
    'credentials' => [
        'key'    => getenv('S3_ACCESS_KEY') ?: '', // 从环境变量读取
        'secret' => getenv('S3_SECRET_KEY') ?: '', // 从环境变量读取
    ],
    'http' => [
        'verify' => false // 禁用 SSL 验证
    ]
]);

$bucketName = getenv('S3_BUCKET_NAME') ?: ''; // 从环境变量读取存储桶名称
$prefix = getenv('S3_PREFIX') ?: ''; // 可选：指定存储桶中的文件夹路径
$cdnDomain = getenv('CDN_DOMAIN') ?: ''; // 从环境变量读取 CDN 域名

// 支持的图片扩展名
$imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

// 获取 S3 存储桶中的文件列表
function getImagesFromS3($s3Client, $bucketName, $prefix, $imageExtensions) {
    $images = [];
    try {
        $result = $s3Client->listObjectsV2([
            'Bucket' => $bucketName,
            'Prefix' => $prefix,
        ]);

        foreach ($result['Contents'] as $object) {
            $file = $object['Key'];
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, $imageExtensions)) {
                $images[] = $file;
            }
        }
    } catch (AwsException $e) {
        echo "错误: " . $e->getMessage() . "\n";
    }
    return $images;
}

// 生成图片的访问 URL
function getImageUrl($s3Client, $bucketName, $key, $cdnDomain = '', $expires = '+1 hour') {
    if (!empty($cdnDomain)) {
        // 如果配置了 CDN 域名，直接拼接 CDN URL
        return rtrim($cdnDomain, '/') . '/' . ltrim($key, '/');
    } else {
        // 如果没有 CDN，使用 S3 预签名 URL
        try {
            $cmd = $s3Client->getCommand('GetObject', [
                'Bucket' => $bucketName,
                'Key'    => $key,
            ]);
            $request = $s3Client->createPresignedRequest($cmd, $expires);
            return (string)$request->getUri();
        } catch (AwsException $e) {
            echo "生成预签名 URL 失败: " . $e->getMessage() . "\n";
            return '';
        }
    }
}

// 获取图片列表并生成 URL
$imageFiles = getImagesFromS3($s3Client, $bucketName, $prefix, $imageExtensions);
?>

<!DOCTYPE HTML>
<html>
    <head>
        <title><?php echo getenv('TITLE') ?: 'Multiverse by HTML5 UP'; ?> </title>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
        <link rel="stylesheet" href="assets/css/main.css" />
    </head>
    <body>
        <!-- Wrapper -->
        <div id="wrapper">
            <!-- Header -->
            <header id="header">
                <h1><a href="/"><strong>HOME</strong></a></h1>
                <nav>
                    <ul>
                        <li><a href="#footer" class="icon fa-info-circle">About</a></li>
                    </ul>
                </nav>
            </header>

            <!-- Main -->
            <div id="main">
                <?php
                foreach ($imageFiles as $image) {
                    $imageUrl = getImageUrl($s3Client, $bucketName, $image, $cdnDomain);
                    if ($imageUrl) {
                        echo '
                        <article class="thumb">
                            <a href="' . htmlspecialchars($imageUrl) . '" class="image"><img src="' . htmlspecialchars($imageUrl) . '" loading="lazy" /></a>
                            <h2>Magna feugiat lorem</h2>
                            <p>Nunc blandit nisi ligula magna sodales lectus elementum non. Integer id venenatis velit.</p>
                        </article>
                        ';
                    }
                }
                ?>
            </div>
            <div class="copyrights">Collect from <a href="https://www.imsun.org">老孙博客</a></div>

            <!-- Footer -->
            <footer id="footer" class="panel">
                <div class="inner split">
                    <div>
                        <section>
                            <h2><?php echo getenv('TITLE') ?: 'Multiverse by HTML5 UP'; ?></h2>
                            <p><?php echo getenv('SECTION_DESCRIPTION') ?: 'Multiverse by HTML5 UP'; ?></p>
                        </section>
                        <section>
                            <h2>Follow me on ...</h2>
                            <ul class="icons">
                                <li><a href="<?php echo getenv('TELEGRAM_URL') ?: 'https://t.me/imsunpw'; ?>" class="icon fa-telegram"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M320 72C183 72 72 183 72 320C72 457 183 568 320 568C457 568 568 457 568 320C568 183 457 72 320 72zM435 240.7C431.3 279.9 415.1 375.1 406.9 419C403.4 437.6 396.6 443.8 390 444.4C375.6 445.7 364.7 434.9 350.7 425.7C328.9 411.4 316.5 402.5 295.4 388.5C270.9 372.4 286.8 363.5 300.7 349C304.4 345.2 367.8 287.5 369 282.3C369.2 281.6 369.3 279.2 367.8 277.9C366.3 276.6 364.2 277.1 362.7 277.4C360.5 277.9 325.6 300.9 258.1 346.5C248.2 353.3 239.2 356.6 231.2 356.4C222.3 356.2 205.3 351.4 192.6 347.3C177.1 342.3 164.7 339.6 165.8 331C166.4 326.5 172.5 322 184.2 317.3C256.5 285.8 304.7 265 328.8 255C397.7 226.4 412 221.4 421.3 221.2C423.4 221.2 427.9 221.7 430.9 224.1C432.9 225.8 434.1 228.2 434.4 230.8C434.9 234 435 237.3 434.8 240.6z"/></svg><span class="label">Telegram</span></a></li>
                                <li><a href="<?php echo getenv('TWITTER_URL') ?: '#'; ?>" class="icon fa-twitter"><span class="label">Twitter</span></a></li>
                                <li><a href="<?php echo getenv('FACEBOOK_URL') ?: '#'; ?>" class="icon fa-facebook"><span class="label">Facebook</span></a></li>
                                <li><a href="<?php echo getenv('INSTAGRAM_URL') ?: '#'; ?>" class="icon fa-instagram"><span class="label">Instagram</span></a></li>
                                <li><a href="<?php echo getenv('GITHUB_URL') ?: 'https://github.com/jkjoy'; ?>" class="icon fa-github"><span class="label">GitHub</span></a></li>
                                <li><a href="<?php echo getenv('DRIBBBLE_URL') ?: '#'; ?>" class="icon fa-dribbble"><span class="label">Dribbble</span></a></li>
                                <li><a href="<?php echo getenv('LINKEDIN_URL') ?: '#'; ?>" class="icon fa-linkedin"><span class="label">LinkedIn</span></a></li>
                                <li><a href="<?php echo getenv('MASTODON_URL') ?: 'https://jiong.us/@sun'; ?>" class="icon fa-brands fa-mastodon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M529 243.1C529 145.9 465.3 117.4 465.3 117.4C402.8 88.7 236.7 89 174.8 117.4C174.8 117.4 111.1 145.9 111.1 243.1C111.1 358.8 104.5 502.5 216.7 532.2C257.2 542.9 292 545.2 320 543.6C370.8 540.8 399.3 525.5 399.3 525.5L397.6 488.6C397.6 488.6 361.3 500 320.5 498.7C280.1 497.3 237.5 494.3 230.9 444.7C230.3 440.1 230 435.4 230 430.8C315.6 451.7 388.7 439.9 408.7 437.5C464.8 430.8 513.7 396.2 519.9 364.6C529.7 314.8 528.9 243.1 528.9 243.1zM453.9 368.3L407.3 368.3L407.3 254.1C407.3 204.4 343.3 202.5 343.3 261L343.3 323.5L297 323.5L297 261C297 202.5 233 204.4 233 254.1L233 368.3L186.3 368.3C186.3 246.2 181.1 220.4 204.7 193.3C230.6 164.4 284.5 162.5 308.5 199.4L320.1 218.9L331.7 199.4C355.8 162.3 409.8 164.6 435.5 193.3C459.2 220.6 453.9 246.3 453.9 368.3L453.9 368.3z"/></svg><span class="label">Mastodon</span></a></li>
                            </ul>
                        </section>
                        <p class="copyright">
                            &copy; <?php echo date("Y")?> <?php echo getenv('TITLE') ?: 'Multiverse by HTML5 UP'; ?>
                        </p>
                    </div>
                    <div>
                        <section>
                            <h2><?php echo getenv('FOOTER_TITLE') ?: 'Footer Title'; ?></h2>
                            <p><?php echo getenv('FOOTER_TEXT') ?: 'Footer Text'; ?></p>
                        </section>
                    </div>
                </div>
            </footer>
        </div>
        <!-- Scripts -->
        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/jquery.poptrox.min.js"></script>
        <script src="assets/js/skel.min.js"></script>
        <script src="assets/js/util.js"></script>
        <script src="assets/js/main.js"></script>
    </body>
</html>