import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const sessionData = ref(null)
const sessionTimer = ref(null)
const warningTimer = ref(null)
const isLoading = ref(false)
const showWarning = ref(false)
const checkInterval = ref(null)

export function useSessionManagement() {
  const timeRemaining = computed(() => {
    if (!sessionData.value?.session?.time_remaining_minutes) return 0
    return Math.max(0, sessionData.value.session.time_remaining_minutes)
  })

  const isExpired = computed(() => {
    return sessionData.value?.session?.is_expired || timeRemaining.value <= 0
  })

  const shouldShowWarning = computed(() => {
    return timeRemaining.value <= 10 && timeRemaining.value > 0 && !isExpired.value
  })

  const formatTimeRemaining = computed(() => {
    const minutes = timeRemaining.value
    if (minutes >= 60) {
      const hours = Math.floor(minutes / 60)
      const remainingMinutes = minutes % 60
      return `${hours}h ${remainingMinutes}m`
    }
    return `${minutes}m`
  })

  /**
   * Fetch current session information
   */
  const fetchSessionInfo = async () => {
    try {
      isLoading.value = true
      const response = await axios.get('/api/session/info')
      sessionData.value = response.data
      
      // Update warning display
      showWarning.value = shouldShowWarning.value
      
      return response.data
    } catch (error) {
      if (error.response?.status === 401) {
        handleSessionExpired()
      }
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Check session validity (lightweight)
   */
  const checkSession = async () => {
    try {
      const response = await axios.get('/api/session/check')
      
      if (response.data.valid) {
        // Update time remaining from server
        if (sessionData.value?.session) {
          sessionData.value.session.time_remaining_minutes = response.data.time_remaining_minutes
        }
      }
      
      return response.data
    } catch (error) {
      if (error.response?.status === 401 || error.response?.data?.expired) {
        handleSessionExpired()
      }
      return { valid: false, authenticated: false }
    }
  }

  /**
   * Extend current session
   */
  const extendSession = async () => {
    try {
      const response = await axios.post('/api/session/extend')
      
      if (response.data.success) {
        // Update session data
        if (sessionData.value?.session) {
          sessionData.value.session.expires_at = response.data.expires_at
          sessionData.value.session.time_remaining_minutes = response.data.time_remaining_minutes
        }
        
        showWarning.value = false
        
        // Show success notification
        window.dispatchEvent(new CustomEvent('session-extended', {
          detail: { message: 'Session extended successfully' }
        }))
      }
      
      return response.data
    } catch (error) {
      console.error('Failed to extend session:', error)
      throw error
    }
  }

  /**
   * Handle session expiration
   */
  const handleSessionExpired = () => {
    // Clear all timers
    clearTimers()
    
    // Clear session data
    sessionData.value = null
    
    // Show expiration notification
    window.dispatchEvent(new CustomEvent('session-expired', {
      detail: { message: 'Your session has expired. Please login again.' }
    }))
    
    // Redirect to login
    router.visit('/login', {
      method: 'get',
      data: {},
      replace: true
    })
  }

  /**
   * Start session monitoring
   */
  const startSessionMonitoring = () => {
    // Initial session info fetch
    fetchSessionInfo()
    
    // Set up periodic session checks (every 2 minutes)
    checkInterval.value = setInterval(async () => {
      const result = await checkSession()
      
      if (!result.valid) {
        clearInterval(checkInterval.value)
      } else {
        // Show warning if session is about to expire
        if (shouldShowWarning.value && !showWarning.value) {
          showWarning.value = true
          
          // Dispatch warning event
          window.dispatchEvent(new CustomEvent('session-warning', {
            detail: { 
              timeRemaining: timeRemaining.value,
              message: `Your session will expire in ${formatTimeRemaining.value}. Click to extend.`
            }
          }))
        }
      }
    }, 2 * 60 * 1000) // 2 minutes
  }

  /**
   * Stop session monitoring
   */
  const stopSessionMonitoring = () => {
    clearTimers()
  }

  /**
   * Clear all timers
   */
  const clearTimers = () => {
    if (checkInterval.value) {
      clearInterval(checkInterval.value)
      checkInterval.value = null
    }
    
    if (sessionTimer.value) {
      clearTimeout(sessionTimer.value)
      sessionTimer.value = null
    }
    
    if (warningTimer.value) {
      clearTimeout(warningTimer.value)
      warningTimer.value = null
    }
  }

  /**
   * Dismiss session warning
   */
  const dismissWarning = () => {
    showWarning.value = false
  }

  /**
   * Get active sessions
   */
  const getActiveSessions = async () => {
    try {
      const response = await axios.get('/api/session/active')
      return response.data
    } catch (error) {
      console.error('Failed to get active sessions:', error)
      return { sessions: [] }
    }
  }

  /**
   * Initialize session management on component mount
   */
  onMounted(() => {
    startSessionMonitoring()
  })

  /**
   * Cleanup on component unmount
   */
  onUnmounted(() => {
    stopSessionMonitoring()
  })

  return {
    // State
    sessionData,
    isLoading,
    showWarning,
    timeRemaining,
    isExpired,
    shouldShowWarning,
    formatTimeRemaining,
    
    // Methods
    fetchSessionInfo,
    checkSession,
    extendSession,
    handleSessionExpired,
    startSessionMonitoring,
    stopSessionMonitoring,
    dismissWarning,
    getActiveSessions
  }
}

// Global session event handlers
export function setupGlobalSessionHandlers() {
  // Listen for session warning events
  window.addEventListener('session-warning', (event) => {
    // You can customize this to show notifications in your UI
    console.warn('Session Warning:', event.detail.message)
  })

  // Listen for session expired events
  window.addEventListener('session-expired', (event) => {
    // You can customize this to show notifications in your UI
    console.error('Session Expired:', event.detail.message)
  })

  // Listen for session extended events
  window.addEventListener('session-extended', (event) => {
    // You can customize this to show notifications in your UI
    console.info('Session Extended:', event.detail.message)
  })
}