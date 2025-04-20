<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800">Audit Logs</h1>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md space-y-4 animate-fade-in-down">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Table</label>
          <select v-model="selectedTable" @change="fetchLogs" class="w-full border-gray-300 rounded-md">
            <option value="">All Tables</option>
            <option v-for="table in tableOptions" :key="table" :value="table">{{ table }}</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Action</label>
          <select v-model="selectedAction" @change="fetchLogs" class="w-full border-gray-300 rounded-md">
            <option value="">All Actions</option>
            <option value="created">Created</option>
            <option value="updated">Updated</option>
            <option value="deleted">Deleted</option>
          </select>
        </div>
      </div>

      <div v-if="loading" class="text-center py-10 text-gray-500">Loading...</div>
      <div v-else-if="logs.length === 0" class="text-center py-10 text-gray-500">No audit logs found.</div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full table-auto border mt-4">
          <thead>
          <tr class="bg-gray-100">
            <th class="border px-4 py-2 text-left">Date</th>
            <th class="border px-4 py-2 text-left">Table</th>
            <th class="border px-4 py-2 text-left">Action</th>
            <th class="border px-4 py-2 text-left">Old Value</th>
            <th class="border px-4 py-2 text-left">New Value</th>
            <th class="border px-4 py-2 text-left">User</th>
            <th class="border px-4 py-2 text-left">Record ID</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="log in logs" :key="log.id" class="hover:bg-gray-50">
            <td class="border px-4 py-2">{{ log.created_at }}</td>
            <td class="border px-4 py-2">{{ log.table_name }}</td>
            <td class="border px-4 py-2 capitalize">{{ log.action }}</td>
            <td class="border px-4 py-2">
              <pre class="json-block text-left" @click="toggle(log, 'old')">
                {{ formatJson(log, 'old') }}
              </pre>
                        </td>
                        <td class="border px-4 py-2">
              <pre class="json-block text-left" @click="toggle(log, 'old')">
                {{ formatJson(log, 'new') }}
              </pre>
            </td>
            <td class="border px-4 py-2">{{ log.user_id ?? 'Guest' }}</td>
            <td class="border px-4 py-2">{{ log.record_id }}</td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axiosClient from '../../axios'

const logs = ref([])
const tableOptions = ref([])
const selectedTable = ref('')
const selectedAction = ref('')
const loading = ref(false)
const expandedLogs = ref({})

const toggle = (log, type) => {
  const key = `${log.id}-${type}`
  expandedLogs.value[key] = !expandedLogs.value[key]
}

const formatJson = (log, type) => {
  const key = `${log.id}-${type}`
  let raw = type === 'old' ? log.old_values : log.new_values

  if (!raw) return '(empty)'

  let data
  try {
    data = typeof raw === 'string' ? JSON.parse(raw) : raw
  } catch (e) {
    return raw // fallback to raw if invalid JSON
  }

  if (!data || typeof data !== 'object') return '(invalid)'

  if (expandedLogs.value[key]) {
    return JSON.stringify(data, null, 2)
  }

  const preview = Object.entries(data)
    .slice(0, 3)
    .map(([k, v]) => `${k}: ${typeof v === 'object' ? '[object]' : v}`)
    .join(', ')

  return preview + (Object.keys(data).length > 3 ? ', ...' : '')
}

const fetchLogs = async () => {
  loading.value = true
  try {
    const { data } = await axiosClient.get('/audit-logs', {
      params: {
        table_name: selectedTable.value,
        action: selectedAction.value
      }
    })
    logs.value = data.logs
  } catch (err) {
    console.error('Failed to fetch logs', err)
    logs.value = []
  } finally {
    loading.value = false
  }
}

const fetchTableOptions = async () => {
  try {
    const { data } = await axiosClient.get('/audit-logs/tables')
    tableOptions.value = data.tables
  } catch (err) {
    console.error('Failed to fetch table list', err)
  }
}

onMounted(() => {
  fetchTableOptions()
  fetchLogs()
})
</script>

<style scoped>
.animate-fade-in-down {
  animation: fade-in-down 0.4s ease-out;
}

@keyframes fade-in-down {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.json-block {
  background: #f8f8f8;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 0.5rem;
  white-space: pre-wrap;
  word-wrap: break-word;
  font-family: Consolas, monospace;
  font-size: 13px;
  cursor: pointer;
  max-height: 150px;
  overflow-y: auto;
}

</style>
