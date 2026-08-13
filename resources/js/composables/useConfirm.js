import { ref } from 'vue'

const isVisible = ref(false)
const confirmData = ref({
  title: '',
  message: '',
  confirmText: 'Confirm',
  cancelText: 'Cancel',
  type: 'info', // info, warning, danger
  onConfirm: () => {},
  onCancel: () => {}
})

export function useConfirm() {
  const showConfirm = (options = {}) => {
    return new Promise((resolve, reject) => {
      confirmData.value = {
        title: 'Confirm Action',
        message: 'Are you sure you want to proceed?',
        confirmText: 'Confirm',
        cancelText: 'Cancel',
        type: 'info',
        ...options,
        onConfirm: () => {
          isVisible.value = false
          resolve(true)
        },
        onCancel: () => {
          isVisible.value = false
          resolve(false)
        }
      }
      isVisible.value = true
    })
  }
  
  const hideConfirm = () => {
    isVisible.value = false
  }
  
  const confirm = () => {
    confirmData.value.onConfirm()
  }
  
  const cancel = () => {
    confirmData.value.onCancel()
  }
  
  // Convenience methods
  const confirmDelete = (itemName = 'item') => {
    return showConfirm({
      title: 'Delete Confirmation',
      message: `Are you sure you want to delete this ${itemName}? This action cannot be undone.`,
      confirmText: 'Delete',
      cancelText: 'Cancel',
      type: 'danger'
    })
  }
  
  const confirmAction = (action = 'action') => {
    return showConfirm({
      title: 'Confirm Action',
      message: `Are you sure you want to proceed with this ${action}?`,
      confirmText: 'Proceed',
      cancelText: 'Cancel',
      type: 'warning'
    })
  }
  
  return {
    isVisible,
    confirmData,
    showConfirm,
    hideConfirm,
    confirm,
    cancel,
    confirmDelete,
    confirmAction
  }
}