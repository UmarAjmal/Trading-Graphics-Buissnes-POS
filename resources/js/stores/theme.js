// 🎨 Enhanced Theme System with Icon Animations
import { ref, onMounted, watch } from 'vue'

const isDark = ref(false)
let transitionTimer = null

const triggerThemeTransition = () => {
  if (typeof document === 'undefined') return

  document.documentElement.classList.add('theme-transitioning')
  clearTimeout(transitionTimer)
  transitionTimer = setTimeout(() => {
    document.documentElement.classList.remove('theme-transitioning')
  }, 500)
}

const applyThemeToDocument = () => {
  if (typeof document === 'undefined') return

  document.documentElement.classList.toggle('dark', isDark.value)
  document.documentElement.dataset.theme = isDark.value ? 'dark' : 'light'
  document.documentElement.style.colorScheme = isDark.value ? 'dark' : 'light'
  
  // Update theme toggle icons
  updateThemeToggleIcon()
  
  triggerThemeTransition()
}

const updateThemeToggleIcon = () => {
  const sunIcon = document.querySelector('.sun-icon')
  const moonIcon = document.querySelector('.moon-icon')
  
  if (sunIcon && moonIcon) {
    if (isDark.value) {
      sunIcon.style.display = 'none'
      moonIcon.style.display = 'block'
    } else {
      sunIcon.style.display = 'block'
      moonIcon.style.display = 'none'
    }
  }
}

const initializeTheme = () => {
  const savedTheme = localStorage.getItem('theme')
  const prefersDark = typeof window !== 'undefined' ? window.matchMedia('(prefers-color-scheme: dark)').matches : false

  if (savedTheme === 'dark') {
    isDark.value = true
  } else if (savedTheme === 'light') {
    isDark.value = false
  } else {
    // Use system preference if no saved theme
    isDark.value = prefersDark
    localStorage.setItem('theme', prefersDark ? 'dark' : 'light')
  }

  applyThemeToDocument()
}

const toggleTheme = () => {
  isDark.value = !isDark.value
  applyThemeToDocument()
  localStorage.setItem('theme', isDark.value ? 'dark' : 'light')
  
  // Trigger theme switch animation
  triggerThemeSwitchAnimation()
}

const setTheme = (theme) => {
  isDark.value = theme === 'dark'
  applyThemeToDocument()
  localStorage.setItem('theme', theme)
}

// Theme switch animation with icon rotation
const triggerThemeSwitchAnimation = () => {
  const themeToggle = document.getElementById('themeToggle')
  if (themeToggle) {
    themeToggle.style.transform = 'rotate(360deg)'
    setTimeout(() => {
      themeToggle.style.transform = 'rotate(0deg)'
    }, 300)
  }
}

// Icon interaction handlers
const initializeIconInteractions = () => {
  // Add click animations to all icon buttons
  document.querySelectorAll('.icon-btn, .icon-fab').forEach(button => {
    button.addEventListener('click', (e) => {
      const ripple = createRippleEffect(e.currentTarget, e)
      e.currentTarget.appendChild(ripple)
      
      setTimeout(() => {
        ripple.remove()
      }, 600)
    })
  })

  // Add hover sound effect (optional)
  document.querySelectorAll('.icon-container').forEach(container => {
    container.addEventListener('mouseenter', () => {
      // Add subtle hover feedback
      container.style.transform = 'scale(1.02)'
    })
    
    container.addEventListener('mouseleave', () => {
      container.style.transform = 'scale(1)'
    })
  })
}

// Create ripple effect for button clicks
const createRippleEffect = (button, event) => {
  const ripple = document.createElement('span')
  const rect = button.getBoundingClientRect()
  const size = Math.max(rect.width, rect.height)
  const x = event.clientX - rect.left - size / 2
  const y = event.clientY - rect.top - size / 2
  
  ripple.style.cssText = `
    position: absolute;
    width: ${size}px;
    height: ${size}px;
    left: ${x}px;
    top: ${y}px;
    background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
    border-radius: 50%;
    transform: scale(0);
    animation: ripple 0.6s linear;
    pointer-events: none;
    z-index: 1000;
  `
  
  return ripple
}

// Watch for system theme changes
const watchSystemTheme = () => {
  if (typeof window === 'undefined') return
  
  const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
  mediaQuery.addEventListener('change', (e) => {
    if (!localStorage.getItem('theme')) {
      isDark.value = e.matches
      applyThemeToDocument()
    }
  })
}

// Icon animation utilities
const animateIcon = (selector, animation) => {
  const icon = document.querySelector(selector)
  if (icon) {
    icon.classList.add(`icon--${animation}`)
    setTimeout(() => {
      icon.classList.remove(`icon--${animation}`)
    }, 2000)
  }
}

const pulseIcon = (selector) => animateIcon(selector, 'pulse')
const spinIcon = (selector) => animateIcon(selector, 'spin')
const bounceIcon = (selector) => animateIcon(selector, 'bounce')
const floatIcon = (selector) => animateIcon(selector, 'float')

// Loading state management
const setIconLoading = (selector, isLoading = true) => {
  const container = document.querySelector(selector)
  if (container) {
    if (isLoading) {
      container.classList.add('icon-loading')
    } else {
      container.classList.remove('icon-loading')
    }
  }
}

// Notification badge management
const showNotificationBadge = (selector) => {
  const container = document.querySelector(selector)
  if (container) {
    container.classList.add('icon-container--badge')
  }
}

const hideNotificationBadge = (selector) => {
  const container = document.querySelector(selector)
  if (container) {
    container.classList.remove('icon-container--badge')
  }
}

if (typeof window !== 'undefined') {
  initializeTheme()
  
  // Add ripple animation CSS
  const style = document.createElement('style')
  style.id = 'ripple-styles'
  style.textContent = `
    @keyframes ripple {
      to {
        transform: scale(2);
        opacity: 0;
      }
    }
    
    .icon-container {
      position: relative;
      overflow: hidden;
    }
  `
  document.head.appendChild(style)
  
  // Initialize icon interactions when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      initializeIconInteractions()
      watchSystemTheme()
    })
  } else {
    setTimeout(() => {
      initializeIconInteractions()
      watchSystemTheme()
    }, 100)
  }
}

export function useTheme() {
  return {
    isDark,
    toggleTheme,
    setTheme,
    initializeTheme,
    // Icon animation utilities
    pulseIcon,
    spinIcon,
    bounceIcon,
    floatIcon,
    setIconLoading,
    showNotificationBadge,
    hideNotificationBadge
  }
}
