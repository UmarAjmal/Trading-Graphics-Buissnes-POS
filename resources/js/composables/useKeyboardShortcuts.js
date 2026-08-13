import { ref, onMounted, onUnmounted } from 'vue'

const shortcuts = ref([])
const isHelpVisible = ref(false)

export function useKeyboardShortcuts() {
  const addShortcut = (shortcut) => {
    shortcuts.value.push({
      key: '',
      ctrl: false,
      alt: false,
      shift: false,
      description: '',
      action: () => {},
      ...shortcut
    })
  }
  
  const removeShortcut = (key) => {
    const index = shortcuts.value.findIndex(s => s.key === key)
    if (index > -1) {
      shortcuts.value.splice(index, 1)
    }
  }
  
  const handleKeydown = (event) => {
    // Show help modal on '?'
    if (event.key === '?' && !event.ctrlKey && !event.altKey) {
      event.preventDefault()
      toggleHelp()
      return
    }
    
    // Check for registered shortcuts
    shortcuts.value.forEach(shortcut => {
      const keyMatches = event.key.toLowerCase() === shortcut.key.toLowerCase()
      const ctrlMatches = event.ctrlKey === shortcut.ctrl
      const altMatches = event.altKey === shortcut.alt
      const shiftMatches = event.shiftKey === shortcut.shift
      
      if (keyMatches && ctrlMatches && altMatches && shiftMatches) {
        event.preventDefault()
        shortcut.action()
      }
    })
  }
  
  const toggleHelp = () => {
    isHelpVisible.value = !isHelpVisible.value
  }
  
  const initKeyboardShortcuts = () => {
    // Add default shortcuts
    addShortcut({
      key: '?',
      description: 'Show keyboard shortcuts',
      action: toggleHelp
    })
    
    addShortcut({
      key: 's',
      ctrl: true,
      description: 'Save (Ctrl+S)',
      action: () => {
        // Trigger save action
        console.log('Save shortcut triggered')
      }
    })
    
    addShortcut({
      key: 'f',
      ctrl: true,
      description: 'Search (Ctrl+F)',
      action: () => {
        // Focus search input
        const searchInput = document.querySelector('input[type="search"], input[placeholder*="Search"]')
        if (searchInput) {
          searchInput.focus()
        }
      }
    })
    
    addShortcut({
      key: 'Escape',
      description: 'Close modal/cancel (Esc)',
      action: () => {
        // Close any open modals or cancel current action
        isHelpVisible.value = false
      }
    })
    
    // Add event listener
    document.addEventListener('keydown', handleKeydown)
  }
  
  const destroyKeyboardShortcuts = () => {
    document.removeEventListener('keydown', handleKeydown)
    shortcuts.value = []
  }
  
  // Group shortcuts by category for help display
  const groupedShortcuts = () => {
    const groups = {
      'General': shortcuts.value.filter(s => ['?', 'Escape'].includes(s.key)),
      'Navigation': shortcuts.value.filter(s => ['f'].includes(s.key.toLowerCase())),
      'Actions': shortcuts.value.filter(s => ['n', 's'].includes(s.key.toLowerCase()))
    }
    
    return groups
  }
  
  onMounted(() => {
    initKeyboardShortcuts()
  })
  
  onUnmounted(() => {
    destroyKeyboardShortcuts()
  })
  
  return {
    shortcuts,
    isHelpVisible,
    addShortcut,
    removeShortcut,
    toggleHelp,
    groupedShortcuts
  }
}