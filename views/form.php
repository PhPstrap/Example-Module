<?php
/**
 * Example Module Form View
 * 
 * This view demonstrates how to create forms in PhPstrap modules.
 * It shows best practices for:
 * - Form structure and styling
 * - CSRF protection
 * - Field validation
 * - Error handling
 * - Settings integration
 * - Accessibility features
 */

// Ensure this file is being included properly
if (!defined('ABSPATH') && !isset($this)) {
    exit('Direct access not allowed');
}

// Get current settings (passed from the module)
$settings = $this->getSettings();
$form_id = $attributes['id'] ?? 'example-module-form';
$css_class = $attributes['class'] ?? 'example-module-form';

// Check for form submission and errors
$submitted = isset($_POST['example_module_submit']);
$errors = isset($_SESSION['example_module_errors']) ? $_SESSION['example_module_errors'] : [];
$success = isset($_SESSION['example_module_success']) ? $_SESSION['example_module_success'] : false;
$form_data = isset($_SESSION['example_module_form_data']) ? $_SESSION['example_module_form_data'] : [];

// Clear session messages after displaying
if ($submitted) {
    unset($_SESSION['example_module_errors']);
    unset($_SESSION['example_module_success']);
    unset($_SESSION['example_module_form_data']);
}

// Generate CSRF token
$csrf_token = function_exists('wp_create_nonce') ? wp_create_nonce('example_module_form') : 
    (isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : bin2hex(random_bytes(16)));
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = $csrf_token;
}
?>

<div class="<?php echo esc_attr($css_class); ?>" id="<?php echo esc_attr($form_id); ?>">
    
    <?php if ($success): ?>
        <div class="example-module-success" role="alert">
            <strong><?php echo esc_html($settings['success_message'] ?? 'Success!'); ?></strong>
            <p><?php echo esc_html($success); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="example-module-errors" role="alert">
            <strong>Please correct the following errors:</strong>
            <ul>
                <?php foreach ($errors as $field => $error): ?>
                    <li><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" 
          enctype="multipart/form-data" 
          class="example-module-form-element"
          novalidate>
        
        <!-- CSRF Protection -->
        <input type="hidden" name="csrf_token" value="<?php echo esc_attr($csrf_token); ?>">
        <input type="hidden" name="example_module_submit" value="1">
        
        <!-- Form Header -->
        <div class="form-header">
            <h3><?php echo esc_html($settings['welcome_title'] ?? 'Submit Content'); ?></h3>
            <?php if (!empty($settings['welcome_message'])): ?>
                <p class="form-description"><?php echo esc_html($settings['welcome_message']); ?></p>
            <?php endif; ?>
        </div>

        <!-- Form Fields -->
        <div class="form-body">
            
            <!-- Title Field -->
            <div class="form-group <?php echo isset($errors['title']) ? 'has-error' : ''; ?>">
                <label for="example_title" class="form-label">
                    Title <span class="required" aria-label="required">*</span>
                </label>
                <input type="text" 
                       id="example_title" 
                       name="title" 
                       class="form-control" 
                       value="<?php echo esc_attr($form_data['title'] ?? ''); ?>"
                       maxlength="255"
                       required
                       aria-describedby="title-help"
                       aria-invalid="<?php echo isset($errors['title']) ? 'true' : 'false'; ?>">
                <small id="title-help" class="form-help">
                    Enter a descriptive title for your content (maximum 255 characters)
                </small>
                <?php if (isset($errors['title'])): ?>
                    <div class="error-message" role="alert"><?php echo esc_html($errors['title']); ?></div>
                <?php endif; ?>
            </div>

            <!-- Content Type Field -->
            <div class="form-group <?php echo isset($errors['data_type']) ? 'has-error' : ''; ?>">
                <label for="example_type" class="form-label">Content Type</label>
                <select id="example_type" 
                        name="data_type" 
                        class="form-control"
                        aria-describedby="type-help">
                    <option value="general" <?php selected($form_data['data_type'] ?? 'general', 'general'); ?>>
                        General Content
                    </option>
                    <option value="tutorial" <?php selected($form_data['data_type'] ?? '', 'tutorial'); ?>>
                        Tutorial
                    </option>
                    <option value="news" <?php selected($form_data['data_type'] ?? '', 'news'); ?>>
                        News
                    </option>
                    <option value="announcement" <?php selected($form_data['data_type'] ?? '', 'announcement'); ?>>
                        Announcement
                    </option>
                    <option value="other" <?php selected($form_data['data_type'] ?? '', 'other'); ?>>
                        Other
                    </option>
                </select>
                <small id="type-help" class="form-help">
                    Select the type of content you're submitting
                </small>
                <?php if (isset($errors['data_type'])): ?>
                    <div class="error-message" role="alert"><?php echo esc_html($errors['data_type']); ?></div>
                <?php endif; ?>
            </div>

            <!-- Content Field -->
            <div class="form-group <?php echo isset($errors['content']) ? 'has-error' : ''; ?>">
                <label for="example_content" class="form-label">
                    Content <span class="required" aria-label="required">*</span>
                </label>
                <textarea id="example_content" 
                          name="content" 
                          class="form-control" 
                          rows="6"
                          maxlength="<?php echo esc_attr($settings['max_content_length'] ?? 2000); ?>"
                          required
                          aria-describedby="content-help content-counter"
                          aria-invalid="<?php echo isset($errors['content']) ? 'true' : 'false'; ?>"
                          placeholder="Enter your content here..."><?php echo esc_textarea($form_data['content'] ?? ''); ?></textarea>
                <div class="form-help-row">
                    <small id="content-help" class="form-help">
                        Provide detailed content. Basic HTML tags are allowed.
                    </small>
                    <small id="content-counter" class="character-counter">
                        <span class="current">0</span>/<span class="max"><?php echo esc_html($settings['max_content_length'] ?? 2000); ?></span>
                    </small>
                </div>
                <?php if (isset($errors['content'])): ?>
                    <div class="error-message" role="alert"><?php echo esc_html($errors['content']); ?></div>
                <?php endif; ?>
            </div>

            <!-- Tags Field -->
            <div class="form-group <?php echo isset($errors['tags']) ? 'has-error' : ''; ?>">
                <label for="example_tags" class="form-label">Tags</label>
                <input type="text" 
                       id="example_tags" 
                       name="tags" 
                       class="form-control" 
                       value="<?php echo esc_attr($form_data['tags'] ?? ''); ?>"
                       aria-describedby="tags-help"
                       placeholder="tutorial, guide, example">
                <small id="tags-help" class="form-help">
                    Optional: Enter tags separated by commas to help categorize your content
                </small>
                <?php if (isset($errors['tags'])): ?>
                    <div class="error-message" role="alert"><?php echo esc_html($errors['tags']); ?></div>
                <?php endif; ?>
            </div>

            <!-- Priority Field -->
            <div class="form-group">
                <label for="example_priority" class="form-label">Priority</label>
                <select id="example_priority" name="priority" class="form-control" aria-describedby="priority-help">
                    <option value="normal" <?php selected($form_data['priority'] ?? 'normal', 'normal'); ?>>
                        Normal
                    </option>
                    <option value="high" <?php selected($form_data['priority'] ?? '', 'high'); ?>>
                        High
                    </option>
                    <option value="low" <?php selected($form_data['priority'] ?? '', 'low'); ?>>
                        Low
                    </option>
                </select>
                <small id="priority-help" class="form-help">
                    Set the display priority for this content
                </small>
            </div>

            <!-- Status Field -->
            <div class="form-group">
                <fieldset>
                    <legend class="form-label">Status</legend>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" 
                                   name="status" 
                                   value="active" 
                                   <?php checked($form_data['status'] ?? 'active', 'active'); ?>>
                            <span>Active</span>
                            <small>Content will be visible immediately</small>
                        </label>
                        <label class="radio-label">
                            <input type="radio" 
                                   name="status" 
                                   value="draft" 
                                   <?php checked($form_data['status'] ?? '', 'draft'); ?>>
                            <span>Draft</span>
                            <small>Save as draft for later publication</small>
                        </label>
                    </div>
                </fieldset>
            </div>

            <!-- File Upload (Optional) -->
            <?php if ($settings['allow_uploads'] ?? false): ?>
            <div class="form-group <?php echo isset($errors['attachment']) ? 'has-error' : ''; ?>">
                <label for="example_attachment" class="form-label">Attachment</label>
                <input type="file" 
                       id="example_attachment" 
                       name="attachment" 
                       class="form-control" 
                       accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx"
                       aria-describedby="attachment-help">
                <small id="attachment-help" class="form-help">
                    Optional: Upload an image or document (max 5MB). Allowed: JPG, PNG, GIF, PDF, DOC, DOCX
                </small>
                <?php if (isset($errors['attachment'])): ?>
                    <div class="error-message" role="alert"><?php echo esc_html($errors['attachment']); ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Author Information -->
            <div class="form-group">
                <label for="example_author" class="form-label">Your Name</label>
                <input type="text" 
                       id="example_author" 
                       name="author_name" 
                       class="form-control" 
                       value="<?php echo esc_attr($form_data['author_name'] ?? (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '')); ?>"
                       maxlength="100"
                       aria-describedby="author-help">
                <small id="author-help" class="form-help">
                    Your name will be displayed with the content
                </small>
            </div>

            <!-- Email Field -->
            <div class="form-group <?php echo isset($errors['author_email']) ? 'has-error' : ''; ?>">
                <label for="example_email" class="form-label">Email Address</label>
                <input type="email" 
                       id="example_email" 
                       name="author_email" 
                       class="form-control" 
                       value="<?php echo esc_attr($form_data['author_email'] ?? (isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '')); ?>"
                       aria-describedby="email-help">
                <small id="email-help" class="form-help">
                    We'll use this to notify you about your submission (not displayed publicly)
                </small>
                <?php if (isset($errors['author_email'])): ?>
                    <div class="error-message" role="alert"><?php echo esc_html($errors['author_email']); ?></div>
                <?php endif; ?>
            </div>

            <!-- Terms and Conditions -->
            <div class="form-group <?php echo isset($errors['terms']) ? 'has-error' : ''; ?>">
                <label class="checkbox-label">
                    <input type="checkbox" 
                           name="terms_accepted" 
                           value="1" 
                           <?php checked($form_data['terms_accepted'] ?? false, '1'); ?>
                           required
                           aria-describedby="terms-help">
                    <span>I agree to the <a href="/terms" target="_blank">Terms of Service</a> and <a href="/privacy" target="_blank">Privacy Policy</a></span>
                    <span class="required" aria-label="required">*</span>
                </label>
                <small id="terms-help" class="form-help">
                    You must agree to our terms to submit content
                </small>
                <?php if (isset($errors['terms'])): ?>
                    <div class="error-message" role="alert"><?php echo esc_html($errors['terms']); ?></div>
                <?php endif; ?>
            </div>

            <!-- Honeypot for spam protection -->
            <div class="honeypot" style="display: none;" aria-hidden="true">
                <label for="example_website">Website</label>
                <input type="text" id="example_website" name="website" value="" tabindex="-1">
            </div>

        </div>

        <!-- Form Footer -->
        <div class="form-footer">
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span class="btn-text">Submit Content</span>
                    <span class="btn-loading" style="display: none;">
                        <span class="spinner"></span> Submitting...
                    </span>
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                    Reset Form
                </button>
            </div>
            
            <div class="form-info">
                <small>
                    <strong>Note:</strong> All submissions are reviewed before publication. 
                    You'll receive an email confirmation once your content is processed.
                </small>
            </div>
        </div>

    </form>
</div>

<!-- Form JavaScript -->
<script>
(function() {
    'use strict';
    
    // Character counter
    const contentField = document.getElementById('example_content');
    const counterCurrent = document.querySelector('#content-counter .current');
    const counterMax = document.querySelector('#content-counter .max');
    
    if (contentField && counterCurrent) {
        function updateCounter() {
            const length = contentField.value.length;
            const maxLength = parseInt(counterMax.textContent);
            
            counterCurrent.textContent = length;
            
            // Update styling based on character count
            const percentage = (length / maxLength) * 100;
            if (percentage > 90) {
                counterCurrent.style.color = '#d32f2f';
            } else if (percentage > 75) {
                counterCurrent.style.color = '#f57c00';
            } else {
                counterCurrent.style.color = '#388e3c';
            }
        }
        
        contentField.addEventListener('input', updateCounter);
        updateCounter(); // Initial update
    }
    
    // Form submission handling
    const form = document.querySelector('.example-module-form-element');
    const submitBtn = form.querySelector('button[type="submit"]');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoading = submitBtn.querySelector('.btn-loading');
    
    form.addEventListener('submit', function(e) {
        // Show loading state
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline-flex';
        
        // Basic client-side validation
        const title = document.getElementById('example_title').value.trim();
        const content = document.getElementById('example_content').value.trim();
        const terms = document.querySelector('input[name="terms_accepted"]').checked;
        
        if (!title || !content || !terms) {
            e.preventDefault();
            resetSubmitButton();
            alert('Please fill in all required fields and accept the terms.');
            return false;
        }
        
        // Check honeypot
        if (document.getElementById('example_website').value !== '') {
            e.preventDefault();
            resetSubmitButton();
            return false;
        }
    });
    
    function resetSubmitButton() {
        submitBtn.disabled = false;
        btnText.style.display = 'inline';
        btnLoading.style.display = 'none';
    }
    
    // Reset form function
    window.resetForm = function() {
        if (confirm('Are you sure you want to reset the form? All data will be lost.')) {
            form.reset();
            updateCounter();
            
            // Clear error states
            const errorGroups = document.querySelectorAll('.form-group.has-error');
            errorGroups.forEach(group => group.classList.remove('has-error'));
            
            // Focus first field
            document.getElementById('example_title').focus();
        }
    };
    
    // Auto-save to localStorage (optional)
    if (typeof Storage !== 'undefined') {
        const fields = ['title', 'content', 'data_type', 'tags', 'author_name', 'author_email'];
        
        fields.forEach(fieldName => {
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (field) {
                // Load saved value
                const saved = localStorage.getItem(`example_module_${fieldName}`);
                if (saved && !field.value) {
                    field.value = saved;
                }
                
                // Save on change
                field.addEventListener('input', function() {
                    localStorage.setItem(`example_module_${fieldName}`, this.value);
                });
            }
        });
        
        // Clear saved data on successful submission
        form.addEventListener('submit', function() {
            fields.forEach(fieldName => {
                localStorage.removeItem(`example_module_${fieldName}`);
            });
        });
    }
    
})();
</script>

<?php
// Helper functions for this view
function selected($current, $value) {
    return $current === $value ? 'selected="selected"' : '';
}

function checked($current, $value) {
    return $current == $value ? 'checked="checked"' : '';
}

function esc_attr($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function esc_html($text) {
    return htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');
}

function esc_textarea($text) {
    return htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');
}

function esc_url($url) {
    return filter_var($url, FILTER_SANITIZE_URL);
}
?>