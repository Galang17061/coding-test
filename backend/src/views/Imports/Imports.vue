<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800">Excel Import</h1>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md space-y-4 animate-fade-in-down">
      <div class="flex items-center gap-4">
        <label
          for="excel-file"
          class="cursor-pointer flex items-center gap-2 text-sm text-gray-700 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-md border border-gray-300 transition"
        >
          📂 Choose File
          <input
            id="excel-file"
            ref="fileInput"
            type="file"
            class="hidden"
            accept=".xlsx, .xls"
            @change="handleFileChange"
          />
        </label>
        <span v-if="fileName" class="text-sm text-indigo-600 font-medium truncate max-w-xs">
          {{ fileName }}
        </span>
      </div>

      <div>
        <button
          type="button"
          @click="uploadExcel"
          :disabled="loading"
          class="inline-flex items-center px-6 py-2 text-white text-sm font-semibold bg-indigo-600 hover:bg-indigo-700 rounded-md transition disabled:opacity-50"
        >
          <svg
            v-if="loading"
            class="animate-spin -ml-1 mr-2 h-5 w-5 text-white"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor"
                  d="M4 12a8 8 0 018-8v8z" />
          </svg>
          {{ loading ? "Uploading..." : "Upload Excel" }}
        </button>
      </div>

      <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 flex items-center justify-between">
        <div class="text-indigo-700 text-sm">
          📄 Don’t have the template? Download it here:
        </div>
        <a
          href="/files/template-excel-import.xlsx"
          download
          class="text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded-md transition"
        >
          Download Template
        </a>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import axiosClient from "../../axios";

const fileInput = ref(null);
const fileName = ref("");
const loading = ref(false);

function handleFileChange(e) {
  fileName.value = e.target.files[0]?.name || "";
}

const uploadExcel = async () => {
  const file = fileInput.value?.files[0];
  if (!file) return alert("Please select a file first.");

  const formData = new FormData();
  formData.append("file", file);

  try {
    loading.value = true;
    await axiosClient.post("/excel/import", formData);
    alert("Import successful!");
  } catch (err) {
    alert("Failed to import: " + (err.response?.data?.message || err.message));
  } finally {
    loading.value = false;
  }
};
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

</style>
