/**
 * Admin Error Fix Script
 * Fixes common WordPress admin JavaScript errors
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0.0
 */

(function($) {
    'use strict';

    // Wait for DOM to be ready
    $(document).ready(function() {
        
        /**
         * Fix #1: Prevent duplicate store registration
         * Jetpack and other plugins often try to register stores multiple times
         */
        if (window.wp && window.wp.data && window.wp.data.registerStore) {
            const originalRegisterStore = window.wp.data.registerStore;
            const registeredStores = new Set();
            
            window.wp.data.registerStore = function(storeName, options) {
                // Check if store is already registered
                if (registeredStores.has(storeName)) {
                    console.warn('Store "' + storeName + '" already registered, skipping duplicate registration');
                    return window.wp.data.select(storeName);
                }
                
                try {
                    const result = originalRegisterStore.call(window.wp.data, storeName, options);
                    registeredStores.add(storeName);
                    return result;
                } catch (error) {
                    if (error.message && error.message.includes('already registered')) {
                        console.warn('Store registration prevented:', storeName);
                        registeredStores.add(storeName);
                        return window.wp.data.select(storeName);
                    }
                    throw error;
                }
            };
        }

        /**
         * Fix #2: Handle React key warnings
         * Add unique keys to SVG paths that are missing them
         */
        if (window.React && window.React.createElement) {
            const originalCreateElement = window.React.createElement;
            
            window.React.createElement = function(type, props, ...children) {
                // If creating SVG elements with multiple children, ensure keys
                if (type === 'svg' && children && children.length > 1) {
                    children = children.map((child, index) => {
                        if (child && typeof child === 'object' && !child.key) {
                            return Object.assign({}, child, { key: 'svg-child-' + index });
                        }
                        return child;
                    });
                }
                
                return originalCreateElement.call(window.React, type, props, ...children);
            };
        }

        /**
         * Fix #3: Block editor error handling
         * Catch and handle block editor JavaScript errors
         */
        if (window.wp && window.wp.blocks) {
            // Wrap block registration to catch errors
            if (window.wp.blocks.registerBlockType) {
                const originalRegisterBlockType = window.wp.blocks.registerBlockType;
                
                window.wp.blocks.registerBlockType = function(name, settings) {
                    try {
                        return originalRegisterBlockType.call(window.wp.blocks, name, settings);
                    } catch (error) {
                        console.error('Block registration error for "' + name + '":', error);
                        return null;
                    }
                };
            }
        }

        /**
         * Fix #4: Customizer error handling
         * Prevent customizer crashes
         */
        if (window.wp && window.wp.customize) {
            // Add error boundary for customizer
            const originalBind = window.wp.customize.bind;
            
            if (originalBind) {
                window.wp.customize.bind = function(id, callback) {
                    try {
                        return originalBind.call(window.wp.customize, id, callback);
                    } catch (error) {
                        console.error('Customizer bind error:', error);
                        return null;
                    }
                };
            }
        }

        /**
         * Fix #5: Safe property access helper
         * Prevent "Cannot read properties of undefined" errors
         */
        window.giSafeAccess = function(obj, path, defaultValue) {
            if (!path || typeof path !== 'string') {
                return defaultValue;
            }
            
            const keys = path.split('.');
            let result = obj;
            
            for (let i = 0; i < keys.length; i++) {
                if (result === null || result === undefined || typeof result !== 'object') {
                    return defaultValue;
                }
                result = result[keys[i]];
            }
            
            return result !== undefined ? result : defaultValue;
        };

        /**
         * Fix #6: Console error suppression for known issues
         * Suppress repetitive console errors that don't affect functionality
         */
        if (console && console.error) {
            const originalConsoleError = console.error;
            const suppressedErrors = [
                'Store "jetpack-modules" is already registered',
                'Store "jetpack-ai/logo-generator" is already registered'
            ];
            
            console.error = function(...args) {
                const message = args.join(' ');
                
                // Check if this is a suppressed error
                const shouldSuppress = suppressedErrors.some(function(pattern) {
                    return message.includes(pattern);
                });
                
                if (!shouldSuppress) {
                    originalConsoleError.apply(console, args);
                }
            };
        }

        console.log('✅ Grant Insight admin error fixes loaded');
    });

})(jQuery);
