// Modal System for Project Management Tool
class Modal {
    constructor(title, content) {
        this.title = title;
        this.content = content;
        this.id = 'modal-' + Date.now();
        this.create();
    }

    create() {
        // Create modal overlay
        this.overlay = document.createElement('div');
        this.overlay.className = 'modal-overlay';
        this.overlay.id = this.id;
        
        // Create modal container
        this.modal = document.createElement('div');
        this.modal.className = 'modal';
        
        // Create modal header
        const header = document.createElement('div');
        header.className = 'modal-header';
        header.innerHTML = `
            <h3>${this.title}</h3>
            <button class="modal-close" onclick="Modal.close('${this.id}')">&times;</button>
        `;
        
        // Create modal body
        const body = document.createElement('div');
        body.className = 'modal-body';
        body.innerHTML = this.content;
        
        // Assemble modal
        this.modal.appendChild(header);
        this.modal.appendChild(body);
        this.overlay.appendChild(this.modal);
        
        // Add to DOM
        document.body.appendChild(this.overlay);
        
        // Bind events
        this.bindEvents();
        
        // Focus management
        this.setupFocusManagement();
    }

    bindEvents() {
        // Close on overlay click
        this.overlay.addEventListener('click', (e) => {
            if (e.target === this.overlay) {
                this.close();
            }
        });

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen()) {
                this.close();
            }
        });

        // Prevent body scroll when modal is open
        document.body.style.overflow = 'hidden';
    }

    setupFocusManagement() {
        // Focus first focusable element
        const focusableElements = this.modal.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        if (focusableElements.length > 0) {
            focusableElements[0].focus();
        }

        // Trap focus within modal
        this.modal.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                this.trapFocus(e);
            }
        });
    }

    trapFocus(e) {
        const focusableElements = this.modal.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        if (e.shiftKey) {
            if (document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            }
        } else {
            if (document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        }
    }

    show() {
        this.overlay.classList.add('active');
        this.overlay.style.display = 'flex';
        
        // Trigger animation
        setTimeout(() => {
            this.modal.style.transform = 'translateY(0)';
            this.modal.style.opacity = '1';
        }, 10);
    }

    close() {
        this.modal.style.transform = 'translateY(-50px)';
        this.modal.style.opacity = '0';
        
        setTimeout(() => {
            this.overlay.classList.remove('active');
            this.overlay.style.display = 'none';
            document.body.style.overflow = '';
            this.destroy();
        }, 300);
    }

    isOpen() {
        return this.overlay.classList.contains('active');
    }

    destroy() {
        if (this.overlay && this.overlay.parentNode) {
            this.overlay.parentNode.removeChild(this.overlay);
        }
    }

    // Static method to close any modal
    static close(modalId = null) {
        if (modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                const modalInstance = new Modal('', '');
                modalInstance.overlay = modal;
                modalInstance.close();
            }
        } else {
            // Close all modals
            document.querySelectorAll('.modal-overlay.active').forEach(overlay => {
                overlay.classList.remove('active');
                overlay.style.display = 'none';
            });
            document.body.style.overflow = '';
        }
    }

    // Static method to show confirmation dialog
    static confirm(message, onConfirm, onCancel) {
        const modal = new Modal('Confirm Action', `
            <div class="confirm-dialog">
                <p>${message}</p>
                <div class="confirm-actions">
                    <button class="btn btn-primary" id="confirm-yes">Yes</button>
                    <button class="btn btn-secondary" id="confirm-no">No</button>
                </div>
            </div>
        `);

        modal.show();

        // Bind confirmation events
        document.getElementById('confirm-yes').addEventListener('click', () => {
            modal.close();
            if (onConfirm) onConfirm();
        });

        document.getElementById('confirm-no').addEventListener('click', () => {
            modal.close();
            if (onCancel) onCancel();
        });

        return modal;
    }

    // Static method to show alert
    static alert(message, onClose) {
        const modal = new Modal('Information', `
            <div class="alert-dialog">
                <p>${message}</p>
                <div class="alert-actions">
                    <button class="btn btn-primary" id="alert-ok">OK</button>
                </div>
            </div>
        `);

        modal.show();

        // Bind close event
        document.getElementById('alert-ok').addEventListener('click', () => {
            modal.close();
            if (onClose) onClose();
        });

        return modal;
    }

    // Static method to show loading modal
    static loading(message = 'Loading...') {
        const modal = new Modal('', `
            <div class="loading-dialog">
                <div class="loading-spinner"></div>
                <p>${message}</p>
            </div>
        `);

        modal.show();
        return modal;
    }

    // Method to update modal content
    updateContent(newContent) {
        const body = this.modal.querySelector('.modal-body');
        if (body) {
            body.innerHTML = newContent;
        }
    }

    // Method to update modal title
    updateTitle(newTitle) {
        const title = this.modal.querySelector('.modal-header h3');
        if (title) {
            title.textContent = newTitle;
        }
    }

    // Method to add custom CSS classes
    addClass(className) {
        this.modal.classList.add(className);
    }

    // Method to remove custom CSS classes
    removeClass(className) {
        this.modal.classList.remove(className);
    }

    // Method to set modal size
    setSize(size) {
        const sizes = {
            'small': 'max-width: 400px',
            'medium': 'max-width: 600px',
            'large': 'max-width: 800px',
            'full': 'max-width: 95vw'
        };

        if (sizes[size]) {
            this.modal.style.cssText += sizes[size];
        }
    }

    // Method to add footer
    addFooter(footerContent) {
        const footer = document.createElement('div');
        footer.className = 'modal-footer';
        footer.innerHTML = footerContent;
        this.modal.appendChild(footer);
    }

    // Method to add custom close button
    addCustomCloseButton(buttonText, onClick) {
        const header = this.modal.querySelector('.modal-header');
        const customButton = document.createElement('button');
        customButton.className = 'btn btn-secondary btn-sm';
        customButton.textContent = buttonText;
        customButton.addEventListener('click', onClick);
        
        header.insertBefore(customButton, header.querySelector('.modal-close'));
    }
}

// Enhanced Modal with additional features
class EnhancedModal extends Modal {
    constructor(title, content, options = {}) {
        super(title, content);
        this.options = {
            size: 'medium',
            closable: true,
            draggable: false,
            resizable: false,
            ...options
        };
        
        this.applyOptions();
    }

    applyOptions() {
        // Set size
        this.setSize(this.options.size);

        // Make non-closable
        if (!this.options.closable) {
            const closeButton = this.modal.querySelector('.modal-close');
            if (closeButton) {
                closeButton.style.display = 'none';
            }
        }

        // Make draggable
        if (this.options.draggable) {
            this.makeDraggable();
        }

        // Make resizable
        if (this.options.resizable) {
            this.makeResizable();
        }
    }

    makeDraggable() {
        const header = this.modal.querySelector('.modal-header');
        let isDragging = false;
        let currentX;
        let currentY;
        let initialX;
        let initialY;
        let xOffset = 0;
        let yOffset = 0;

        header.style.cursor = 'move';
        header.style.userSelect = 'none';

        header.addEventListener('mousedown', (e) => {
            initialX = e.clientX - xOffset;
            initialY = e.clientY - yOffset;
            isDragging = true;
        });

        document.addEventListener('mousemove', (e) => {
            if (isDragging) {
                e.preventDefault();
                currentX = e.clientX - initialX;
                currentY = e.clientY - initialY;
                xOffset = currentX;
                yOffset = currentY;

                this.modal.style.transform = `translate(${currentX}px, ${currentY}px)`;
            }
        });

        document.addEventListener('mouseup', () => {
            isDragging = false;
        });
    }

    makeResizable() {
        const resizer = document.createElement('div');
        resizer.className = 'modal-resizer';
        resizer.style.cssText = `
            position: absolute;
            bottom: 0;
            right: 0;
            width: 20px;
            height: 20px;
            cursor: se-resize;
            background: linear-gradient(-45deg, transparent 30%, #ccc 30%, #ccc 40%, transparent 40%);
        `;

        this.modal.style.position = 'relative';
        this.modal.appendChild(resizer);

        let isResizing = false;
        let startWidth, startHeight, startX, startY;

        resizer.addEventListener('mousedown', (e) => {
            isResizing = true;
            startWidth = this.modal.offsetWidth;
            startHeight = this.modal.offsetHeight;
            startX = e.clientX;
            startY = e.clientY;
        });

        document.addEventListener('mousemove', (e) => {
            if (isResizing) {
                const newWidth = startWidth + (e.clientX - startX);
                const newHeight = startHeight + (e.clientY - startY);
                
                this.modal.style.width = `${newWidth}px`;
                this.modal.style.height = `${newHeight}px`;
            }
        });

        document.addEventListener('mouseup', () => {
            isResizing = false;
        });
    }

    // Method to add tabs
    addTabs(tabs) {
        const tabContainer = document.createElement('div');
        tabContainer.className = 'modal-tabs';
        
        const tabHeaders = document.createElement('div');
        tabHeaders.className = 'tab-headers';
        
        const tabContent = document.createElement('div');
        tabContent.className = 'tab-content';
        
        tabs.forEach((tab, index) => {
            const tabHeader = document.createElement('button');
            tabHeader.className = `tab-header ${index === 0 ? 'active' : ''}`;
            tabHeader.textContent = tab.title;
            tabHeader.addEventListener('click', () => this.switchTab(index, tabHeaders, tabContent, tabs));
            
            tabHeaders.appendChild(tabHeader);
            
            const tabPane = document.createElement('div');
            tabPane.className = `tab-pane ${index === 0 ? 'active' : ''}`;
            tabPane.innerHTML = tab.content;
            
            tabContent.appendChild(tabPane);
        });
        
        tabContainer.appendChild(tabHeaders);
        tabContainer.appendChild(tabContent);
        
        const body = this.modal.querySelector('.modal-body');
        body.insertBefore(tabContainer, body.firstChild);
    }

    switchTab(index, headers, content, tabs) {
        // Update tab headers
        headers.querySelectorAll('.tab-header').forEach((header, i) => {
            header.classList.toggle('active', i === index);
        });
        
        // Update tab content
        content.querySelectorAll('.tab-pane').forEach((pane, i) => {
            pane.classList.toggle('active', i === index);
        });
    }

    // Method to add form validation
    addFormValidation(formSelector, validationRules) {
        const form = this.modal.querySelector(formSelector);
        if (!form) return;

        form.addEventListener('submit', (e) => {
            if (!this.validateForm(form, validationRules)) {
                e.preventDefault();
            }
        });
    }

    validateForm(form, rules) {
        let isValid = true;
        
        Object.keys(rules).forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                const value = field.value.trim();
                const rule = rules[fieldName];
                
                if (rule.required && !value) {
                    this.showFieldError(field, rule.message || 'This field is required');
                    isValid = false;
                } else if (rule.pattern && !rule.pattern.test(value)) {
                    this.showFieldError(field, rule.message || 'Invalid format');
                    isValid = false;
                } else {
                    this.clearFieldError(field);
                }
            }
        });
        
        return isValid;
    }

    showFieldError(field, message) {
        this.clearFieldError(field);
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        errorDiv.style.cssText = 'color: #dc3545; font-size: 0.8rem; margin-top: 5px;';
        
        field.parentNode.appendChild(errorDiv);
        field.style.borderColor = '#dc3545';
    }

    clearFieldError(field) {
        const errorDiv = field.parentNode.querySelector('.field-error');
        if (errorDiv) {
            errorDiv.remove();
        }
        field.style.borderColor = '';
    }
}

// Utility functions for modal management
const ModalUtils = {
    // Show multiple modals in sequence
    showSequence: async (modals) => {
        for (const modalConfig of modals) {
            const modal = new Modal(modalConfig.title, modalConfig.content);
            modal.show();
            
            // Wait for modal to be closed
            await new Promise(resolve => {
                modal.overlay.addEventListener('click', (e) => {
                    if (e.target === modal.overlay) {
                        resolve();
                    }
                });
            });
        }
    },

    // Create modal from template
    fromTemplate: (templateName, data) => {
        const templates = {
            'project-form': `
                <form class="project-form">
                    <div class="form-group">
                        <label>Project Name</label>
                        <input type="text" name="name" value="${data.name || ''}" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description">${data.description || ''}</textarea>
                    </div>
                </form>
            `,
            'task-form': `
                <form class="task-form">
                    <div class="form-group">
                        <label>Task Title</label>
                        <input type="text" name="title" value="${data.title || ''}" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description">${data.description || ''}</textarea>
                    </div>
                </form>
            `
        };
        
        return templates[templateName] || '';
    },

    // Create modal with dynamic content loading
    withDynamicContent: (title, contentUrl, onLoad) => {
        const modal = new Modal(title, '<div class="loading">Loading...</div>');
        modal.show();
        
        fetch(contentUrl)
            .then(response => response.text())
            .then(content => {
                modal.updateContent(content);
                if (onLoad) onLoad(modal);
            })
            .catch(error => {
                modal.updateContent(`<div class="error">Error loading content: ${error.message}</div>`);
            });
        
        return modal;
    }
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { Modal, EnhancedModal, ModalUtils };
}
