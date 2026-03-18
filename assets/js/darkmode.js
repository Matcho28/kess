/**
 * Dark Mode Toggle Functionality
 * Shared across all pages
 */

class DarkModeManager {
    constructor() {
        this.init();
    }

    init() {
        // Check for saved preference on page load
        this.loadSavedPreference();
        
        // Create and append the dark mode toggle button
        this.createToggleButton();
        
        // Add event listeners
        this.addEventListeners();
        
        // Apply initial state
        this.applyInitialState();
    }

    loadSavedPreference() {
        const savedDarkMode = localStorage.getItem('darkMode');
        if (savedDarkMode === 'true') {
            document.body.classList.add('dark-mode');
        }
    }

    createToggleButton() {
        // Check if button already exists
        if (document.getElementById('darkModeToggle')) {
            return;
        }

        const button = document.createElement('button');
        button.id = 'darkModeToggle';
        button.className = 'dark-mode-toggle';
        button.title = 'Toggle dark mode';
        button.innerHTML = '<i class="fas fa-moon"></i>';
        
        // Add CSS styles
        button.style.cssText = `
            position: fixed;
            top: 1rem;
            right: 2rem;
            z-index: 1001;
            background: var(--accent, #19c37d);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(25, 195, 125, 0.3);
        `;

        // Add hover effect
        button.addEventListener('mouseenter', () => {
            button.style.transform = 'scale(1.1)';
            button.style.boxShadow = '0 6px 20px rgba(25, 195, 125, 0.4)';
        });

        button.addEventListener('mouseleave', () => {
            button.style.transform = 'scale(1)';
            button.style.boxShadow = '0 4px 15px rgba(25, 195, 125, 0.3)';
        });

        document.body.appendChild(button);
    }

    addEventListeners() {
        const toggleButton = document.getElementById('darkModeToggle');
        if (!toggleButton) return;

        // Toggle dark mode on button click
        toggleButton.addEventListener('click', () => {
            this.toggleDarkMode();
        });

        // Keyboard shortcut (Ctrl/Cmd + D)
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
                e.preventDefault();
                this.toggleDarkMode();
            }
        });

        // Listen for system theme changes
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQuery.addListener((e) => {
                if (!localStorage.getItem('darkMode')) {
                    // Only apply if user hasn't explicitly set preference
                    if (e.matches) {
                        document.body.classList.add('dark-mode');
                    } else {
                        document.body.classList.remove('dark-mode');
                    }
                }
            });
        }
    }

    toggleDarkMode() {
        const body = document.body;
        const toggleButton = document.getElementById('darkModeToggle');
        const icon = toggleButton.querySelector('i');
        
        body.classList.toggle('dark-mode');
        const isDarkMode = body.classList.contains('dark-mode');
        
        // Update icon
        if (isDarkMode) {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        } else {
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        }
        
        // Save preference
        localStorage.setItem('darkMode', isDarkMode);
        
        // Update charts if they exist
        this.updateCharts(isDarkMode);
        
        // Dispatch custom event for other components
        window.dispatchEvent(new CustomEvent('darkModeChanged', { 
            detail: { isDarkMode } 
        }));
    }

    applyInitialState() {
        const body = document.body;
        const toggleButton = document.getElementById('darkModeToggle');
        if (!toggleButton) return;
        
        const icon = toggleButton.querySelector('i');
        const isDarkMode = body.classList.contains('dark-mode');
        
        if (isDarkMode) {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        }
    }

    updateCharts(isDarkMode) {
        // Update Chart.js instances if they exist
        if (typeof Chart !== 'undefined') {
            const textColor = isDarkMode ? '#f1f5f9' : '#1e293b';
            const gridColor = isDarkMode ? 'rgba(148, 163, 184, 0.1)' : 'rgba(148, 163, 184, 0.2)';
            
            Chart.helpers.each(Chart.instances, (instance) => {
                if (instance.options.plugins?.legend?.labels) {
                    instance.options.plugins.legend.labels.color = textColor;
                }
                
                if (instance.options.scales?.y) {
                    instance.options.scales.y.grid.color = gridColor;
                    instance.options.scales.y.ticks.color = textColor;
                }
                
                if (instance.options.scales?.x) {
                    instance.options.scales.x.ticks.color = textColor;
                }
                
                instance.update();
            });
        }
    }

    // Public method to get current state
    isDarkMode() {
        return document.body.classList.contains('dark-mode');
    }

    // Public method to set dark mode programmatically
    setDarkMode(enabled) {
        const body = document.body;
        const toggleButton = document.getElementById('darkModeToggle');
        
        if (enabled && !body.classList.contains('dark-mode')) {
            this.toggleDarkMode();
        } else if (!enabled && body.classList.contains('dark-mode')) {
            this.toggleDarkMode();
        }
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.darkModeManager = new DarkModeManager();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DarkModeManager;
}
