import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class', // Make sure you are toggling the 'dark' class on <html>
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/Filament/**/*.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Outfit', ...defaultTheme.fontFamily.sans],
                display: ['Syne', ...defaultTheme.fontFamily.sans], // Great choice for headers
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // IMPROVED: Neutral palette using Midnight Sapphires (Deep Dark Anime)
                // Use these for background, borders, and main text.
                // Replaces the generic "dark" slate grays.
                neutral: {
                    50: '#F1F5F9', // Light theme background hint
                    100: '#E1E9F1', // Light theme borders
                    200: '#C6D0F5', // Dark theme main text
                    300: '#94A3B8', // Dark theme muted text
                    400: '#64748B',
                    500: '#404B69', // Borders (dark) / subtle backgrounds
                    600: '#212A42', // Card backgrounds (dark)
                    700: '#151C30',
                    800: '#0B1121', // Dark UI background
                    900: '#070B14', // Section background (darker)
                    950: '#04060C', // Root background (darkest)
                },

                // IMPROVED: Brand Primary: Vibrant Violet (Replaces Default Indigo)
                // This is the main action color for buttons, active states, and logos.
                primary: {
                    50: '#F5F3FF',
                    100: '#EDE9FE',
                    200: '#DDD6FE',
                    300: '#C4B5FD',
                    400: '#A78BFA', // Hover states (dark)
                    500: '#8B5CF6', // Primary Main (dark theme accent)
                    600: '#7C3AED', // Primary Main (light theme accent)
                    700: '#6D28D9',
                    800: '#5B21B6',
                    900: '#4C1D95',
                    950: '#2E1065',
                },

                // NEW: Semantic Accent Colors (System States)
                // We integrated your "Neon" utility colors here.
                accent: {
                    // INFO: Integrated your "Neon Blue" (#00D4FF) but slightly cleaner
                    info: '#00E5FF', 
                    
                    // SUCCESS: Keeping your exact "Neon Green"
                    success: '#10B981', 
                    
                    // WARNING: Keeping your exact "Neon Gold"
                    warning: '#F59E0B', 
                    
                    // DANGER: Using a vibrant "Neon Pink" as danger instead of muted red.
                    danger: '#EC4899', 
                },
            },
            backgroundImage: {
                'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
                
                // IMPROVED: The gradient mesh uses the new Primary Violet instead of old blue.
                'gradient-mesh': 'linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%)',
                
                'hero-pattern': "url(\"data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%239C92AC' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E\")",
            },
            animation: {
                // Your excellent animations remain unchanged.
                'fade-in': 'fadeIn 0.3s ease-out',
                'fade-up': 'fadeUp 0.4s ease-out',
                'slide-down': 'slideDown 0.3s ease-out',
                'slide-up': 'slideUp 0.3s ease-out',
                'scale-in': 'scaleIn 0.2s ease-out',
                'shimmer': 'shimmer 2s infinite linear',
                'pulse-glow': 'pulseGlow 2s ease-in-out infinite',
                'float': 'float 6s ease-in-out infinite',
                'spin-slow': 'spin 8s linear infinite',
                'bounce-subtle': 'bounceSubtle 2s ease-in-out infinite',
            },
            keyframes: {
                // Your excellent keyframes remain unchanged.
                fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                fadeUp: { '0%': { opacity: '0', transform: 'translateY(20px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                slideDown: { '0%': { opacity: '0', transform: 'translateY(-10px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                slideUp: { '0%': { opacity: '0', transform: 'translateY(10px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                scaleIn: { '0%': { opacity: '0', transform: 'scale(0.95)' }, '100%': { opacity: '1', transform: 'scale(1)' } },
                shimmer: { '0%': { backgroundPosition: '-1000px 0' }, '100%': { backgroundPosition: '1000px 0' } },
                pulseGlow: { // IMPROVED: Using the new Primary color for glow
                    '0%, 100%': { boxShadow: '0 0 20px rgba(139, 92, 246, 0.4)' },
                    '50%': { boxShadow: '0 0 40px rgba(139, 92, 246, 0.8)' },
                },
                float: { '0%, 100%': { transform: 'translateY(0px)' }, '50%': { transform: 'translateY(-20px)' } },
                bounceSubtle: { '0%, 100%': { transform: 'translateY(-3%)' }, '50%': { transform: 'translateY(0)' } },
            },
            boxShadow: {
                // IMPROVED: Standardized existing neon shadows using semantic names
                'neon-info': '0 0 20px rgba(0, 229, 255, 0.5)', // Electric Cyan
                'neon-primary': '0 0 20px rgba(139, 92, 246, 0.5)', // Vibrant Violet
                'neon-success': '0 0 20px rgba(16, 185, 129, 0.5)', // Neon Green
                'neon-danger': '0 0 20px rgba(236, 72, 153, 0.5)', // Neon Pink

                // IMPROVED: Dark theme card shadows use a midnight blue tint for depth.
                'card': '0 4px 20px rgba(4, 6, 12, 0.4)',
                'card-hover': '0 8px 40px rgba(4, 6, 12, 0.6)',
                'glass': '0 8px 32px rgba(4, 6, 12, 0.3)',
            },
            backdropBlur: {
                xs: '2px',
            },
            borderRadius: {
                // Updated slightly cleaner names for Tailwind standard compatibility.
                'xl': '1rem',
                '2xl': '1.25rem',
                '3xl': '1.5rem',
                '4xl': '2rem',
            },
            screens: {
                'xs': '475px',
            },
            transitionTimingFunction: {
                'bounce-in': 'cubic-bezier(0.68, -0.55, 0.265, 1.55)',
                'smooth': 'cubic-bezier(0.4, 0, 0.2, 1)',
            },
        },
    },
    plugins: [forms, typography],
};