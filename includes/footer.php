    </div> <!-- Close container -->

    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>About KilimoConnect</h5>
                    <p>Connecting farmers directly with buyers for a better agricultural marketplace.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>?page=about" class="text-white">About Us</a></li>
                        <li><a href="<?php echo SITE_URL; ?>?page=contact" class="text-white">Contact Us</a></li>
                        <li><a href="<?php echo SITE_URL; ?>?page=terms" class="text-white">Terms of Service</a></li>
                        <li><a href="<?php echo SITE_URL; ?>?page=privacy" class="text-white">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Info</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope"></i> <?php echo ADMIN_EMAIL; ?></li>
                        <li><i class="fas fa-phone"></i> +255 628 030 877</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
</body>
</html> 