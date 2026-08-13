<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <div class="flex justify-center">
          <img src="/Narmer_3D_Logo.png" alt="Logo" class="h-20 w-auto" />
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Sign in to your account
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Narmer POS Systems - Pakistan Printing & Panaflex
        </p>
      </div>
      
      <form class="mt-8 space-y-6" @submit.prevent="submit">
        <div class="rounded-md shadow-sm -space-y-px">
          <div>
            <label for="email" class="sr-only">Email address</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              autocomplete="email"
              required
              class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
              :class="{ 'border-red-500': form.errors.email }"
              placeholder="Email address"
            />
            <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">
              {{ form.errors.email }}
            </div>
          </div>
          
          <div>
            <label for="password" class="sr-only">Password</label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              autocomplete="current-password"
              required
              class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
              :class="{ 'border-red-500': form.errors.password }"
              placeholder="Password"
            />
            <div v-if="form.errors.password" class="text-red-500 text-sm mt-1">
              {{ form.errors.password }}
            </div>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input
              id="remember"
              v-model="form.remember"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="remember" class="ml-2 block text-sm text-gray-900">
              Remember me
            </label>
          </div>
          
           <div class="text-sm">
            <a href="#" @click.prevent="showSupportModal = true" class="font-medium text-indigo-600 hover:text-indigo-500">
              Forgot your password?
            </a>
          </div>
        </div>

        <div>
          <button
            type="submit"
            :disabled="form.processing"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            <span v-if="form.processing">
              Signing in...
            </span>
            <span v-else>
              Sign in
            </span>
          </button>
        </div>

        <!-- Contact Support Modal -->
        <div v-if="showSupportModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
           <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4 relative">
              <button @click="showSupportModal = false" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600">
                 <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
              </button>
              <div class="text-center">
                  <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Account Recovery</h3>
                  <div class="mt-2 text-sm text-gray-600">
                    <p>For gaining access to System Administrator, please contact:</p>
                    <div class="mt-4 p-4 bg-gray-50 rounded text-left space-y-2">
                        <p class="font-bold text-indigo-700">Narmer Solutions</p>
                        <p><strong>Phone:</strong> 03263392082</p>
                        <p><strong>Email:</strong> narmersolutions@gmail.com</p>
                        <p><strong>Website:</strong> www.narmersolutions.com</p>
                    </div>
                  </div>
                  <div class="mt-6">
                    <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm" @click="showSupportModal = false">
                      Close
                    </button>
                  </div>
              </div>
           </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { useForm, Link } from '@inertiajs/vue3'
import { ref } from 'vue'

export default {
  name: 'Login',
  components: {
    Link
  },
  props: {
    canResetPassword: Boolean,
    status: String,
  },
  setup() {
    const showSupportModal = ref(false)
    const form = useForm({
      email: '',
      password: '',
      remember: false,
    })

    const submit = () => {
      form.post(route('login'), {
        onFinish: () => form.reset('password'),
      })
    }

    return {
      form,
      submit,
      showSupportModal
    }
  },
}
</script>