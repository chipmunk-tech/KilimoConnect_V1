<?php
$page_title = "Page Not Found - " . SITE_NAME;
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="error-page">
                <h1 class="display-1 text-muted">404</h1>
                <h2 class="mb-4">Page Not Found</h2>
                <p class="lead mb-5">The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
                
                <div class="d-flex justify-content-center gap-3">
                    <a href="<?php echo SITE_URL; ?>" class="btn btn-success">
                        <i class="fas fa-home me-2"></i>Go to Homepage
                    </a>
                    <button onclick="history.back()" class="btn btn-outline-success">
                        <i class="fas fa-arrow-left me-2"></i>Go Back
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.error-page {
    padding: 100px 0;
}
.error-page h1 {
    font-size: 120px;
    font-weight: 700;
    margin-bottom: 20px;
}
.error-page h2 {
    font-size: 32px;
    font-weight: 600;
    margin-bottom: 20px;
}
.error-page p {
    font-size: 18px;
    color: #6c757d;
}
</style> 