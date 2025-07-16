/**
 * Example Module JavaScript
 * 
 * This JavaScript file demonstrates professional frontend development for PhPstrap modules.
 * It includes:
 * - Progressive enhancement
 * - AJAX form submissions
 * - Real-time validation
 * - User experience enhancements
 * - Accessibility improvements
 * - Error handling and recovery
 */

(function(window, document) {
    'use strict';

    // Module namespace
    const ExampleModule = {
        version: '1.0.0',
        debug: false,
        initialized: false,
        
        // Configuration
        config: {
            ajaxUrl: '/wp-admin/admin-ajax.php', // Adjust for PhPstrap
            nonce: '',
            autoSaveInterval: 30000, // 30 seconds
            maxFileSize: 5 * 1024 * 1024, // 5MB
            allowedFileTypes: ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']
        },
        
        // Storage for module data
        data: {
            forms: new Map(),
            timers: new Map(),
            cache: new Map()
        }
    };

    /**
     * Initialize the module
     */
    ExampleModule.init = function() {
        if (this.initialized) {
            return;
        }

        this.log('Initializing Example Module v' + this.version);

        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.setup();
            });
        } else {
            this.setup();
        }

        this.initialized = true;
    };

    /**
     * Setup module functionality
     */
    ExampleModule.setup = function() {
        // Initialize forms
        this.initializeForms();
        
        // Initialize widgets
        this.initializeWidgets();
        
        // Initialize data displays
        this.initializeDataDisplays();
        
        // Setup global event listeners
        this.setupGlobalEvents();
        
        // Initialize accessibility features
        this.initializeAccessibility();
        
        this.log('Example Module setup complete');
    };

    /**
     * Initialize all forms on the page
     */
    ExampleModule.initializeForms = function() {
        const forms = document.querySelectorAll('.example-module-form-element');
        
        forms.forEach(form => {
            this.initializeForm(form);
        });
    };

    /**
     * Initialize a single form
     */
    ExampleModule.initializeForm = function(form) {
        if (!form || form.dataset.initialized) {
            return;
        }

        const formId = form.id || 'form-' + Date.now();
        form.id = formId;
        form.dataset.initialized = 'true';

        // Store form reference
        this.data.forms.set(formId, {
            element: form,
            validator: null,
            autoSaveTimer: null,
            originalData: new FormData(form)
        });

        // Setup form features
        this.setupFormValidation(form);
        this.setupFormSubmission(form);
        this.setupAutoSave(form);
        this.setupCharacterCounters(form);
        this.setupFileUpload(form);
        this.setupFormEnhancements(form);

        this.log('Form initialized: ' + formId);
    };

    /**
     * Setup real-time form validation
     */
    ExampleModule.setupFormValidation = function(form) {
        const fields = form.querySelectorAll('.form-control');
        
        fields.forEach(field => {
            // Real-time validation on blur
            field.addEventListener('blur', (e) => {
                this.validateField(e.target);
            });

            // Clear errors on input
            field.addEventListener('input', (e) => {
                this.clearFieldError(e.target);
            });
        });

        // Form submission validation
        form.addEventListener('submit', (e) => {
            if (!this.validateForm(form)) {
                e.preventDefault();
                this.focusFirstError(form);
            }
        });
    };

    /**
     * Validate a single field
     */
    ExampleModule.validateField = function(field) {
        const fieldName = field.name;
        const value = field.value.trim();
        const isRequired = field.hasAttribute('required');
        const fieldType = field.type;
        
        let isValid = true;
        let errorMessage = '';

        // Required field validation
        if (isRequired && !value) {
            isValid = false;
            errorMessage = 'This field is required.';
        }
        // Email validation
        else if (fieldType === 'email' && value && !this.isValidEmail(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address.';
        }
        // URL validation
        else if (fieldType === 'url' && value && !this.isValidUrl(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid URL.';
        }
        // Character limits
        else if (field.hasAttribute('maxlength')) {
            const maxLength = parseInt(field.getAttribute('maxlength'));
            if (value.length > maxLength) {
                isValid = false;
                errorMessage = `Maximum ${maxLength} characters allowed.`;
            }
        }
        // Custom validation patterns
        else if (field.hasAttribute('pattern')) {
            const pattern = new RegExp(field.getAttribute('pattern'));
            if (value && !pattern.test(value)) {
                isValid = false;
                errorMessage = field.getAttribute('title') || 'Invalid format.';
            }
        }

        // Update field state
        this.updateFieldValidation(field, isValid, errorMessage);
        
        return isValid;
    };

    /**
     * Validate entire form
     */
    ExampleModule.validateForm = function(form) {
        const fields = form.querySelectorAll('.form-control[required], .form-control[pattern]');
        let isValid = true;

        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        // Custom validations
        isValid = this.validateCustomRules(form) && isValid;

        return isValid;
    };

    /**
     * Custom validation rules
     */
    ExampleModule.validateCustomRules = function(form) {
        let isValid = true;

        // Terms acceptance validation
        const termsCheckbox = form.querySelector('input[name="terms_accepted"]');
        if (termsCheckbox && !termsCheckbox.checked) {
            this.updateFieldValidation(termsCheckbox, false, 'You must accept the terms and conditions.');
            isValid = false;
        }

        // Honeypot validation
        const honeypot = form.querySelector('input[name="website"]');
        if (honeypot && honeypot.value) {
            // Silent failure for spam bots
            isValid = false;
        }

        return isValid;
    };

    /**
     * Update field validation state
     */
    ExampleModule.updateFieldValidation = function(field, isValid, errorMessage) {
        const formGroup = field.closest('.form-group');
        const existingError = formGroup.querySelector('.error-message');

        if (isValid) {
            formGroup.classList.remove('has-error');
            field.setAttribute('aria-invalid', 'false');
            if (existingError) {
                existingError.remove();
            }
        } else {
            formGroup.classList.add('has-error');
            field.setAttribute('aria-invalid', 'true');
            
            if (!existingError && errorMessage) {
                const errorElement = document.createElement('div');
                errorElement.className = 'error-message';
                errorElement.setAttribute('role', 'alert');
                errorElement.textContent = errorMessage;
                field.parentNode.appendChild(errorElement);
            }
        }
    };

    /**
     * Clear field error state
     */
    ExampleModule.clearFieldError = function(field) {
        const formGroup = field.closest('.form-group');
        const errorMessage = formGroup.querySelector('.error-message');
        
        if (formGroup.classList.contains('has-error')) {
            formGroup.classList.remove('has-error');
            field.setAttribute('aria-invalid', 'false');
            
            if (errorMessage) {
                errorMessage.remove();
            }
        }
    };

    /**
     * Focus first error field
     */
    ExampleModule.focusFirstError = function(form) {
        const firstError = form.querySelector('.has-error .form-control');
        if (firstError) {
            firstError.focus();
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    };

    /**
     * Setup AJAX form submission
     */
    ExampleModule.setupFormSubmission = function(form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            if (this.validateForm(form)) {
                this.submitForm(form);
            }
        });
    };

    /**
     * Submit form via AJAX
     */
    ExampleModule.submitForm = function(form) {
        const formId = form.id;
        const submitButton = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);
        
        // Update UI state
        this.setFormLoading(form, true);
        
        // Add AJAX flag
        formData.append('ajax', '1');
        formData.append('action', 'example_module_submit');
        
        fetch(form.action || window.location.href, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            this.handleFormResponse(form, data);
        })
        .catch(error => {
            this.handleFormError(form, error);
        })
        .finally(() => {
            this.setFormLoading(form, false);
        });
    };

    /**
     * Handle form submission response
     */
    ExampleModule.handleFormResponse = function(form, response) {
        if (response.success) {
            this.showSuccessMessage(form, response.message || 'Form submitted successfully!');
            this.clearAutoSave(form.id);
            form.reset();
            this.updateCharacterCounters(form);
            
            // Trigger custom event
            this.triggerEvent('exampleModuleFormSuccess', {
                form: form,
                response: response
            });
        } else {
            this.showFormErrors(form, response.errors || {});
            this.focusFirstError(form);
        }
    };

    /**
     * Handle form submission error
     */
    ExampleModule.handleFormError = function(form, error) {
        this.log('Form submission error:', error);
        
        const errorMessage = 'An error occurred while submitting the form. Please try again.';
        this.showErrorMessage(form, errorMessage);
    };

    /**
     * Set form loading state
     */
    ExampleModule.setFormLoading = function(form, isLoading) {
        const submitButton = form.querySelector('button[type="submit"]');
        const btnText = submitButton.querySelector('.btn-text');
        const btnLoading = submitButton.querySelector('.btn-loading');
        
        submitButton.disabled = isLoading;
        
        if (btnText && btnLoading) {
            btnText.style.display = isLoading ? 'none' : 'inline';
            btnLoading.style.display = isLoading ? 'inline-flex' : 'none';
        } else {
            submitButton.textContent = isLoading ? 'Submitting...' : 'Submit';
        }
        
        // Add loading class to form
        form.classList.toggle('is-loading', isLoading);
    };

    /**
     * Show success message
     */
    ExampleModule.showSuccessMessage = function(form, message) {
        this.removeExistingMessages(form);
        
        const successDiv = document.createElement('div');
        successDiv.className = 'example-module-success';
        successDiv.setAttribute('role', 'alert');
        successDiv.innerHTML = `<strong>Success!</strong><p>${this.escapeHtml(message)}</p>`;
        
        form.parentNode.insertBefore(successDiv, form);
        successDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            if (successDiv.parentNode) {
                successDiv.remove();
            }
        }, 5000);
    };

    /**
     * Show error message
     */
    ExampleModule.showErrorMessage = function(form, message) {
        this.removeExistingMessages(form);
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'example-module-errors';
        errorDiv.setAttribute('role', 'alert');
        errorDiv.innerHTML = `<strong>Error!</strong><p>${this.escapeHtml(message)}</p>`;
        
        form.parentNode.insertBefore(errorDiv, form);
        errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    /**
     * Show form field errors
     */
    ExampleModule.showFormErrors = function(form, errors) {
        // Clear existing errors
        form.querySelectorAll('.has-error').forEach(group => {
            group.classList.remove('has-error');
        });
        form.querySelectorAll('.error-message').forEach(msg => {
            msg.remove();
        });

        // Show new errors
        Object.keys(errors).forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                this.updateFieldValidation(field, false, errors[fieldName]);
            }
        });

        // Show general error message if no field-specific errors
        if (Object.keys(errors).length === 0) {
            this.showErrorMessage(form, 'Please correct the errors and try again.');
        }
    };

    /**
     * Remove existing success/error messages
     */
    ExampleModule.removeExistingMessages = function(form) {
        const container = form.parentNode;
        const existingMessages = container.querySelectorAll('.example-module-success, .example-module-errors');
        existingMessages.forEach(msg => msg.remove());
    };

    /**
     * Setup auto-save functionality
     */
    ExampleModule.setupAutoSave = function(form) {
        if (!window.localStorage) {
            return; // Auto-save requires localStorage
        }

        const formId = form.id;
        const autoSaveKey = `example_module_autosave_${formId}`;
        
        // Load saved data
        this.loadAutoSave(form, autoSaveKey);
        
        // Setup auto-save timer
        const fields = form.querySelectorAll('.form-control');
        fields.forEach(field => {
            field.addEventListener('input', () => {
                this.debounceAutoSave(form, autoSaveKey);
            });
        });
    };

    /**
     * Load auto-saved data
     */
    ExampleModule.loadAutoSave = function(form, autoSaveKey) {
        try {
            const savedData = localStorage.getItem(autoSaveKey);
            if (savedData) {
                const data = JSON.parse(savedData);
                const now = Date.now();
                
                // Check if data is not too old (1 hour)
                if (now - data.timestamp < 3600000) {
                    this.restoreFormData(form, data.values);
                    this.showAutoSaveNotification(form);
                } else {
                    localStorage.removeItem(autoSaveKey);
                }
            }
        } catch (error) {
            this.log('Auto-save load error:', error);
        }
    };

    /**
     * Debounced auto-save
     */
    ExampleModule.debounceAutoSave = function(form, autoSaveKey) {
        const formId = form.id;
        
        // Clear existing timer
        if (this.data.timers.has(formId)) {
            clearTimeout(this.data.timers.get(formId));
        }
        
        // Set new timer
        const timer = setTimeout(() => {
            this.saveFormData(form, autoSaveKey);
        }, 2000); // 2 second delay
        
        this.data.timers.set(formId, timer);
    };

    /**
     * Save form data to localStorage
     */
    ExampleModule.saveFormData = function(form, autoSaveKey) {
        try {
            const formData = new FormData(form);
            const values = {};
            
            for (let [key, value] of formData.entries()) {
                // Skip sensitive fields
                if (['csrf_token', 'website', 'terms_accepted'].includes(key)) {
                    continue;
                }
                values[key] = value;
            }
            
            const saveData = {
                timestamp: Date.now(),
                values: values
            };
            
            localStorage.setItem(autoSaveKey, JSON.stringify(saveData));
        } catch (error) {
            this.log('Auto-save error:', error);
        }
    };

    /**
     * Restore form data
     */
    ExampleModule.restoreFormData = function(form, values) {
        Object.keys(values).forEach(name => {
            const field = form.querySelector(`[name="${name}"]`);
            if (field && !field.value) {
                field.value = values[name];
                
                // Trigger events for character counters
                field.dispatchEvent(new Event('input'));
            }
        });
    };

    /**
     * Show auto-save notification
     */
    ExampleModule.showAutoSaveNotification = function(form) {
        const notification = document.createElement('div');
        notification.className = 'auto-save-notification';
        notification.innerHTML = '<small>💾 Previous draft restored</small>';
        notification.style.cssText = `
            background: #e3f2fd;
            border: 1px solid #1976d2;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 16px;
            color: #1565c0;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        `;
        
        form.parentNode.insertBefore(notification, form);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    };

    /**
     * Clear auto-save data
     */
    ExampleModule.clearAutoSave = function(formId) {
        const autoSaveKey = `example_module_autosave_${formId}`;
        localStorage.removeItem(autoSaveKey);
    };

    /**
     * Setup character counters
     */
    ExampleModule.setupCharacterCounters = function(form) {
        const fieldsWithCounters = form.querySelectorAll('[maxlength]');
        
        fieldsWithCounters.forEach(field => {
            const counter = form.querySelector(`#${field.id.replace('_', '-')}-counter, #${field.getAttribute('aria-describedby')}`);
            if (counter) {
                this.initializeCharacterCounter(field, counter);
            }
        });
    };

    /**
     * Initialize character counter for a field
     */
    ExampleModule.initializeCharacterCounter = function(field, counter) {
        const updateCounter = () => {
            const current = field.value.length;
            const max = parseInt(field.getAttribute('maxlength'));
            const currentSpan = counter.querySelector('.current');
            const maxSpan = counter.querySelector('.max');
            
            if (currentSpan) {
                currentSpan.textContent = current;
            }
            if (maxSpan) {
                maxSpan.textContent = max;
            }
            
            // Update styling based on usage
            const percentage = (current / max) * 100;
            let color = '#059669'; // green
            
            if (percentage > 90) {
                color = '#dc2626'; // red
            } else if (percentage > 75) {
                color = '#d97706'; // orange
            }
            
            if (currentSpan) {
                currentSpan.style.color = color;
            }
        };
        
        field.addEventListener('input', updateCounter);
        updateCounter(); // Initial update
    };

    /**
     * Update all character counters in form
     */
    ExampleModule.updateCharacterCounters = function(form) {
        const fields = form.querySelectorAll('[maxlength]');
        fields.forEach(field => {
            field.dispatchEvent(new Event('input'));
        });
    };

    /**
     * Setup file upload enhancements
     */
    ExampleModule.setupFileUpload = function(form) {
        const fileInputs = form.querySelectorAll('input[type="file"]');
        
        fileInputs.forEach(input => {
            this.enhanceFileInput(input);
        });
    };

    /**
     * Enhance file input with drag & drop and validation
     */
    ExampleModule.enhanceFileInput = function(input) {
        const wrapper = document.createElement('div');
        wrapper.className = 'file-upload-wrapper';
        
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        
        // Create drop zone
        const dropZone = document.createElement('div');
        dropZone.className = 'file-drop-zone';
        dropZone.innerHTML = `
            <div class="drop-zone-content">
                <span class="drop-icon">📁</span>
                <span class="drop-text">Drop files here or click to browse</span>
                <span class="drop-hint">Maximum file size: ${this.formatFileSize(this.config.maxFileSize)}</span>
            </div>
        `;
        
        wrapper.appendChild(dropZone);
        
        // File info display
        const fileInfo = document.createElement('div');
        fileInfo.className = 'file-info';
        wrapper.appendChild(fileInfo);
        
        // Event listeners
        input.addEventListener('change', () => {
            this.handleFileSelection(input, fileInfo);
        });
        
        // Drag & drop
        dropZone.addEventListener('click', () => {
            input.click();
        });
        
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });
        
        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('drag-over');
        });
        
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                input.files = files;
                this.handleFileSelection(input, fileInfo);
            }
        });
    };

    /**
     * Handle file selection
     */
    ExampleModule.handleFileSelection = function(input, fileInfo) {
        const file = input.files[0];
        
        if (!file) {
            fileInfo.innerHTML = '';
            return;
        }
        
        // Validate file
        const validation = this.validateFile(file);
        
        if (validation.valid) {
            fileInfo.innerHTML = `
                <div class="file-selected">
                    <span class="file-name">${this.escapeHtml(file.name)}</span>
                    <span class="file-size">${this.formatFileSize(file.size)}</span>
                    <button type="button" class="file-remove" onclick="this.closest('.file-upload-wrapper').querySelector('input').value=''; this.parentNode.parentNode.innerHTML='';">×</button>
                </div>
            `;
        } else {
            fileInfo.innerHTML = `
                <div class="file-error">
                    <span class="error-text">${this.escapeHtml(validation.error)}</span>
                </div>
            `;
            input.value = '';
        }
    };

    /**
     * Validate uploaded file
     */
    ExampleModule.validateFile = function(file) {
        // Check file size
        if (file.size > this.config.maxFileSize) {
            return {
                valid: false,
                error: `File too large. Maximum size is ${this.formatFileSize(this.config.maxFileSize)}.`
            };
        }
        
        // Check file type
        const extension = file.name.split('.').pop().toLowerCase();
        if (!this.config.allowedFileTypes.includes(extension)) {
            return {
                valid: false,
                error: `File type not allowed. Allowed types: ${this.config.allowedFileTypes.join(', ')}.`
            };
        }
        
        return { valid: true };
    };

    /**
     * Setup form enhancements
     */
    ExampleModule.setupFormEnhancements = function(form) {
        // Reset button functionality
        const resetButton = form.querySelector('button[type="button"]');
        if (resetButton && resetButton.textContent.includes('Reset')) {
            resetButton.addEventListener('click', () => {
                this.resetForm(form);
            });
        }
        
        // Add form progress indicator
        this.addFormProgress(form);
        
        // Setup conditional fields
        this.setupConditionalFields(form);
    };

    /**
     * Reset form with confirmation
     */
    ExampleModule.resetForm = function(form) {
        if (confirm('Are you sure you want to reset the form? All unsaved data will be lost.')) {
            form.reset();
            
            // Clear errors
            form.querySelectorAll('.has-error').forEach(group => {
                group.classList.remove('has-error');
            });
            form.querySelectorAll('.error-message').forEach(msg => {
                msg.remove();
            });
            
            // Clear auto-save
            this.clearAutoSave(form.id);
            
            // Update counters
            this.updateCharacterCounters(form);
            
            // Focus first field
            const firstField = form.querySelector('.form-control');
            if (firstField) {
                firstField.focus();
            }
            
            // Remove existing messages
            this.removeExistingMessages(form);
        }
    };

    /**
     * Add form progress indicator
     */
    ExampleModule.addFormProgress = function(form) {
        const requiredFields = form.querySelectorAll('.form-control[required]');
        if (requiredFields.length === 0) {
            return;
        }
        
        const progressContainer = document.createElement('div');
        progressContainer.className = 'form-progress';
        progressContainer.innerHTML = `
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            <span class="progress-text">0% complete</span>
        `;
        
        const formHeader = form.querySelector('.form-header');
        if (formHeader) {
            formHeader.appendChild(progressContainer);
        }
        
        // Update progress on field changes
        const updateProgress = () => {
            const filledFields = Array.from(requiredFields).filter(field => {
                return field.value.trim() !== '';
            }).length;
            
            const percentage = Math.round((filledFields / requiredFields.length) * 100);
            const progressFill = progressContainer.querySelector('.progress-fill');
            const progressText = progressContainer.querySelector('.progress-text');
            
            progressFill.style.width = percentage + '%';
            progressText.textContent = percentage + '% complete';
        };
        
        requiredFields.forEach(field => {
            field.addEventListener('input', updateProgress);
            field.addEventListener('change', updateProgress);
        });
        
        updateProgress(); // Initial update
    };

    /**
     * Setup conditional fields
     */
    ExampleModule.setupConditionalFields = function(form) {
        // Example: Show/hide fields based on selections
        const typeField = form.querySelector('[name="data_type"]');
        if (typeField) {
            const conditionalField = form.querySelector('[data-depends-on="data_type"]');
            if (conditionalField) {
                const toggleConditional = () => {
                    const showValues = conditionalField.dataset.showWhen.split(',');
                    const shouldShow = showValues.includes(typeField.value);
                    conditionalField.style.display = shouldShow ? 'block' : 'none';
                };
                
                typeField.addEventListener('change', toggleConditional);
                toggleConditional(); // Initial state
            }
        }
    };

    /**
     * Initialize widgets
     */
    ExampleModule.initializeWidgets = function() {
        const widgets = document.querySelectorAll('.example-module-widget');
        
        widgets.forEach(widget => {
            this.enhanceWidget(widget);
        });
    };

    /**
     * Enhance widget functionality
     */
    ExampleModule.enhanceWidget = function(widget) {
        // Add interactive features
        if (widget.classList.contains('interactive')) {
            this.makeWidgetInteractive(widget);
        }
        
        // Add animation on scroll
        this.addScrollAnimation(widget);
    };

    /**
     * Make widget interactive
     */
    ExampleModule.makeWidgetInteractive = function(widget) {
        widget.addEventListener('click', () => {
            widget.classList.toggle('expanded');
        });
        
        widget.style.cursor = 'pointer';
        widget.setAttribute('role', 'button');
        widget.setAttribute('tabindex', '0');
        
        // Keyboard support
        widget.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                widget.click();
            }
        });
    };

    /**
     * Add scroll animation
     */
    ExampleModule.addScrollAnimation = function(element) {
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });
            
            observer.observe(element);
        }
    };

    /**
     * Initialize data displays
     */
    ExampleModule.initializeDataDisplays = function() {
        const dataContainers = document.querySelectorAll('.example-module-data');
        
        dataContainers.forEach(container => {
            this.enhanceDataDisplay(container);
        });
    };

    /**
     * Enhance data display
     */
    ExampleModule.enhanceDataDisplay = function(container) {
        // Add search functionality
        this.addDataSearch(container);
        
        // Add sorting
        this.addDataSorting(container);
        
        // Add filtering
        this.addDataFiltering(container);
        
        // Add pagination if needed
        this.addDataPagination(container);
    };

    /**
     * Add search functionality to data display
     */
    ExampleModule.addDataSearch = function(container) {
        const items = container.querySelectorAll('.data-item');
        if (items.length <= 3) {
            return; // Don't add search for small lists
        }
        
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.placeholder = 'Search content...';
        searchInput.className = 'data-search';
        searchInput.style.cssText = `
            width: 100%;
            padding: 8px 12px;
            margin-bottom: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        `;
        
        container.insertBefore(searchInput, container.firstChild);
        
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase();
            
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                const matches = text.includes(query);
                item.style.display = matches ? 'block' : 'none';
            });
            
            // Show "no results" message
            const visibleItems = Array.from(items).filter(item => item.style.display !== 'none');
            let noResultsMsg = container.querySelector('.no-results');
            
            if (visibleItems.length === 0 && query) {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.className = 'no-results';
                    noResultsMsg.textContent = 'No matching content found.';
                    noResultsMsg.style.cssText = 'text-align: center; color: #666; padding: 20px; font-style: italic;';
                    container.appendChild(noResultsMsg);
                }
            } else if (noResultsMsg) {
                noResultsMsg.remove();
            }
        });
    };

    /**
     * Add sorting functionality
     */
    ExampleModule.addDataSorting = function(container) {
        const items = container.querySelectorAll('.data-item');
        if (items.length <= 2) {
            return;
        }
        
        const sortSelect = document.createElement('select');
        sortSelect.className = 'data-sort';
        sortSelect.innerHTML = `
            <option value="date-desc">Newest first</option>
            <option value="date-asc">Oldest first</option>
            <option value="title-asc">Title A-Z</option>
            <option value="title-desc">Title Z-A</option>
        `;
        sortSelect.style.cssText = `
            padding: 6px 8px;
            margin-bottom: 16px;
            margin-left: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        `;
        
        const searchInput = container.querySelector('.data-search');
        if (searchInput) {
            searchInput.parentNode.insertBefore(sortSelect, searchInput.nextSibling);
        } else {
            container.insertBefore(sortSelect, container.firstChild);
        }
        
        sortSelect.addEventListener('change', () => {
            this.sortDataItems(container, sortSelect.value);
        });
    };

    /**
     * Sort data items
     */
    ExampleModule.sortDataItems = function(container, sortType) {
        const items = Array.from(container.querySelectorAll('.data-item'));
        
        items.sort((a, b) => {
            switch (sortType) {
                case 'date-desc':
                    return new Date(b.querySelector('small').textContent) - new Date(a.querySelector('small').textContent);
                case 'date-asc':
                    return new Date(a.querySelector('small').textContent) - new Date(b.querySelector('small').textContent);
                case 'title-asc':
                    return a.querySelector('h4').textContent.localeCompare(b.querySelector('h4').textContent);
                case 'title-desc':
                    return b.querySelector('h4').textContent.localeCompare(a.querySelector('h4').textContent);
                default:
                    return 0;
            }
        });
        
        // Re-append items in sorted order
        items.forEach(item => {
            container.appendChild(item);
        });
    };

    /**
     * Add filtering functionality
     */
    ExampleModule.addDataFiltering = function(container) {
        // Implementation depends on data structure
        // This is a placeholder for content type filtering
    };

    /**
     * Add pagination
     */
    ExampleModule.addDataPagination = function(container) {
        const items = container.querySelectorAll('.data-item');
        const itemsPerPage = 5;
        
        if (items.length <= itemsPerPage) {
            return;
        }
        
        // Implementation would add pagination controls
        // This is a simplified version
    };

    /**
     * Setup global event listeners
     */
    ExampleModule.setupGlobalEvents = function() {
        // Handle escape key to close modals/dialogs
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.handleEscapeKey();
            }
        });
        
        // Handle click outside to close dropdowns
        document.addEventListener('click', (e) => {
            this.handleClickOutside(e);
        });
        
        // Handle window resize
        window.addEventListener('resize', this.debounce(() => {
            this.handleResize();
        }, 250));
    };

    /**
     * Initialize accessibility features
     */
    ExampleModule.initializeAccessibility = function() {
        // Add skip links
        this.addSkipLinks();
        
        // Enhance keyboard navigation
        this.enhanceKeyboardNavigation();
        
        // Add ARIA live regions
        this.addLiveRegions();
    };

    /**
     * Add skip links for accessibility
     */
    ExampleModule.addSkipLinks = function() {
        const forms = document.querySelectorAll('.example-module-form');
        
        forms.forEach(form => {
            const skipLink = document.createElement('a');
            skipLink.href = '#' + form.id;
            skipLink.textContent = 'Skip to form';
            skipLink.className = 'skip-link sr-only';
            skipLink.style.cssText = `
                position: absolute;
                top: -40px;
                left: 6px;
                background: #000;
                color: #fff;
                padding: 8px;
                text-decoration: none;
                border-radius: 4px;
                z-index: 1000;
            `;
            
            skipLink.addEventListener('focus', () => {
                skipLink.style.top = '6px';
            });
            
            skipLink.addEventListener('blur', () => {
                skipLink.style.top = '-40px';
            });
            
            document.body.insertBefore(skipLink, document.body.firstChild);
        });
    };

    /**
     * Enhance keyboard navigation
     */
    ExampleModule.enhanceKeyboardNavigation = function() {
        // Add visible focus indicators
        const style = document.createElement('style');
        style.textContent = `
            .example-module-form .form-control:focus,
            .example-module-widget:focus,
            .btn:focus {
                outline: 2px solid #2563eb;
                outline-offset: 2px;
            }
        `;
        document.head.appendChild(style);
    };

    /**
     * Add ARIA live regions for dynamic updates
     */
    ExampleModule.addLiveRegions = function() {
        const liveRegion = document.createElement('div');
        liveRegion.id = 'example-module-live-region';
        liveRegion.setAttribute('aria-live', 'polite');
        liveRegion.setAttribute('aria-atomic', 'true');
        liveRegion.style.cssText = `
            position: absolute;
            left: -10000px;
            width: 1px;
            height: 1px;
            overflow: hidden;
        `;
        
        document.body.appendChild(liveRegion);
    };

    /**
     * Announce message to screen readers
     */
    ExampleModule.announceToScreenReader = function(message) {
        const liveRegion = document.getElementById('example-module-live-region');
        if (liveRegion) {
            liveRegion.textContent = message;
            
            // Clear after announcement
            setTimeout(() => {
                liveRegion.textContent = '';
            }, 1000);
        }
    };

    // Utility functions
    ExampleModule.isValidEmail = function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    };

    ExampleModule.isValidUrl = function(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    };

    ExampleModule.escapeHtml = function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    ExampleModule.formatFileSize = function(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    ExampleModule.debounce = function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };

    ExampleModule.triggerEvent = function(eventName, detail) {
        const event = new CustomEvent(eventName, { detail });
        document.dispatchEvent(event);
    };

    ExampleModule.log = function(...args) {
        if (this.debug) {
            console.log('[Example Module]', ...args);
        }
    };

    ExampleModule.handleEscapeKey = function() {
        // Close any open modals, dropdowns, etc.
        const openElements = document.querySelectorAll('.is-open, .is-expanded');
        openElements.forEach(el => {
            el.classList.remove('is-open', 'is-expanded');
        });
    };

    ExampleModule.handleClickOutside = function(e) {
        // Handle click outside for dropdowns, etc.
        const dropdowns = document.querySelectorAll('.dropdown.is-open');
        dropdowns.forEach(dropdown => {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('is-open');
            }
        });
    };

    ExampleModule.handleResize = function() {
        // Handle responsive changes
        this.log('Window resized');
    };

    // Initialize when script loads
    ExampleModule.init();

    // Expose module to global scope for external access
    window.ExampleModule = ExampleModule;

})(window, document);