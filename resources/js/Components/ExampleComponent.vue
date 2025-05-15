<template>
  <div class="container py-5">
    <h1 class="text-center mb-4">ร้านอาหารแนะนำ</h1>

    <!-- ช่องค้นหา + ปุ่มค้นหา + ปุ่มรัศมี -->
    <div class="d-flex mb-4 flex-wrap gap-2 align-items-center">
      <div class="input-group" style="flex-grow:1; min-width: 250px;">
        <input
          v-model="locationInput"
          type="text"
          class="form-control"
          placeholder="กรอกสถานที่ เช่น บางซื่อ"
          @keyup.enter="searchRestaurants"
        />
        <button class="btn btn-primary" @click="searchRestaurants">ค้นหา</button>
      </div>

      <div>
        <div class="btn-group" role="group" aria-label="รัศมีค้นหา">
          <button
            v-for="r in radii"
            :key="r.value"
            type="button"
            class="btn"
            :class="radius === r.value ? 'btn-primary' : 'btn-outline-primary'"
            @click="setRadius(r.value)"
          >
            {{ r.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Toast ป๊อปอัพลอยขึ้น -->
    <transition name="toast-fade">
      <div v-if="showError" class="toast-popup">
        {{ errorMsg }}
        <button class="btn-close btn-close-white" @click="hideError" aria-label="Close"></button>
      </div>
    </transition>

    <!-- แสดงข้อความกรณีไม่มีข้อมูล (ถ้าไม่มี error) -->
    <div v-if="!showError && restaurants.length === 0" class="alert alert-warning my-3">
      ไม่พบข้อมูลร้านอาหาร
    </div>

    <!-- แสดงร้านอาหาร -->
    <div class="row" v-if="restaurants.length > 0">
      <div
        v-for="place in restaurants"
        :key="place.place_id"
        class="col-sm-6 col-md-3 mb-4"
      >
        <div class="card d-flex flex-column h-100">
          <img
            :src="place.photoUrl || defaultImg"
            class="card-img-top fixed-img-size"
            alt="รูปร้านอาหาร"
          />
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">{{ place.name }}</h5>
            <p class="card-text">{{ place.vicinity || place.formatted_address }}</p>
            <a
              :href="'https://www.google.com/maps/place/?q=place_id:' + place.place_id"
              target="_blank"
              class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 mt-auto"
              title="เปิดใน Google Maps"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="16"
                height="16"
                fill="currentColor"
                class="bi bi-pin-map-fill text-danger"
                viewBox="0 0 16 16"
              >
                <path
                  fill-rule="evenodd"
                  d="M3.1 11.2a.5.5 0 0 1 .4-.2H6a.5.5 0 0 1 0 1H3.75L1.5 15h13l-2.25-3H10a.5.5 0 0 1 0-1h2.5a.5.5 0 0 1 .4.2l3 4a.5.5 0 0 1-.4.8H.5a.5.5 0 0 1-.4-.8z"
                />
                <path
                  fill-rule="evenodd"
                  d="M4 4a4 4 0 1 1 4.5 3.969V13.5a.5.5 0 0 1-1 0V7.97A4 4 0 0 1 4 3.999z"
                />
              </svg>
              <span class="d-none d-md-inline">แผนที่</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const restaurants = ref([])
const errorMsg = ref('')
const showError = ref(false)
const locationInput = ref('บางซื่อ')
const radius = ref(2000)
const defaultImg = 'https://via.placeholder.com/400x300?text=No+Image'

const radii = [
  { label: '100 ม.', value: 100 },
  { label: '200 ม.', value: 200 },
  { label: '500 ม.', value: 500 },
  { label: '1 กิโล', value: 1000 },
]

function showErrorToast(msg) {
  errorMsg.value = msg
  showError.value = true
  // ซ่อนอัตโนมัติหลัง 4 วิ
  setTimeout(() => {
    showError.value = false
  }, 4000)
}

function hideError() {
  showError.value = false
}

async function loadRestaurants(location = 'บางซื่อ', searchRadius = 2000) {
  try {
    const res = await axios.get('http://127.0.0.1:8000/api/restaurants', {
      params: {
        keyword: 'ร้านอาหาร',
        location: location,
        radius: searchRadius,
      },
    })
    restaurants.value = res.data
  } catch (e) {
    if (e.response && e.response.data && e.response.data.error) {
      showErrorToast(e.response.data.error)
    } else {
      showErrorToast('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์')
    }
  }
}

function searchRestaurants() {
  loadRestaurants(locationInput.value.trim() || 'บางซื่อ', radius.value)
}

function setRadius(value) {
  radius.value = value
  searchRestaurants()
}

onMounted(() => {
  loadRestaurants(locationInput.value, radius.value)
})
</script>

<style scoped>
.fixed-img-size {
  width: 100%;
  height: 200px;
  object-fit: cover;
}

/* Toast popup */
.toast-popup {
  position: fixed;
  top: 20px;
  right: 20px;
  background-color: #dc3545; /* สีแดง bootstrap danger */
  color: white;
  padding: 12px 20px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgb(0 0 0 / 0.2);
  z-index: 9999;
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 600;
}

/* ปุ่มปิด Toast */
.toast-popup .btn-close {
  filter: invert(1);
  opacity: 0.8;
  cursor: pointer;
}

/* Animation fade in/out ลอยขึ้น */
.toast-fade-enter-active,
.toast-fade-leave-active {
  transition: opacity 0.5s ease, transform 0.5s ease;
}
.toast-fade-enter-from,
.toast-fade-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}
.toast-fade-enter-to,
.toast-fade-leave-from {
  opacity: 1;
  transform: translateY(0);
}
</style>
