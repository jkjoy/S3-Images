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
                                <li><a href="<?php echo getenv('TELEGRAM_URL') ?: 'https://t.me/imsunpw'; ?>" class="icon fa-telegram"><span class="label">Telegram</span></a></li>
                                <li><a href="<?php echo getenv('TWITTER_URL') ?: '#'; ?>" class="icon fa-twitter"><span class="label">Twitter</span></a></li>
                                <li><a href="<?php echo getenv('FACEBOOK_URL') ?: '#'; ?>" class="icon fa-facebook"><span class="label">Facebook</span></a></li>
                                <li><a href="<?php echo getenv('INSTAGRAM_URL') ?: '#'; ?>" class="icon fa-instagram"><span class="label">Instagram</span></a></li>
                                <li><a href="<?php echo getenv('GITHUB_URL') ?: 'https://github.com/jkjoy'; ?>" class="icon fa-github"><span class="label">GitHub</span></a></li>
                                <li><a href="<?php echo getenv('DRIBBBLE_URL') ?: '#'; ?>" class="icon fa-dribbble"><span class="label">Dribbble</span></a></li>
                                <li><a href="<?php echo getenv('LINKEDIN_URL') ?: '#'; ?>" class="icon fa-linkedin"><span class="label">LinkedIn</span></a></li>
                                <li><a href="<?php echo getenv('MASTODON_URL') ?: 'https://jiong.us/@sun'; ?>" class="icon fa-brands fa-mastodon"><span class="label">Mastodon</span></a></li>
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