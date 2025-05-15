<template>
  <div class="container py-5">
    <h1 class="text-center mb-4">ร้านอาหารแนะนำ</h1>

    <!-- ช่องค้นหา location + ปุ่มค้นหา + ปุ่มรัศมี -->
    <div class="d-flex mb-4 flex-wrap gap-2 align-items-center">
      <div class="input-group" style="flex-grow:1; min-width: 250px;">
        <input v-model="locationInput" type="text" class="form-control" placeholder="กรอกสถานที่ เช่น บางซื่อ"
          @keyup.enter="searchRestaurants" />
        <button class="btn btn-primary" @click="searchRestaurants">ค้นหา</button>
      </div>

      <div>
        <div class="btn-group" role="group" aria-label="รัศมีค้นหา">
          <button v-for="r in radii" :key="r.value" type="button" class="btn"
            :class="radius === r.value ? 'btn-primary' : 'btn-outline-primary'" @click="setRadius(r.value)">
            {{ r.label }}
          </button>
        </div>
      </div>
    </div>

    <div class="row">
      <div v-for="place in restaurants" :key="place.place_id" class="col-sm-6 col-md-3 mb-4">
        <div class="card d-flex flex-column h-100">
          <img :src="place.photoUrl || defaultImg" class="card-img-top fixed-img-size" alt="รูปร้านอาหาร" />

          <div class="card-body d-flex flex-column">
            <h5 class="card-title">{{ place.name }}</h5>
            <p class="card-text">{{ place.vicinity || place.formatted_address }}</p>

            <!-- ปุ่มลิงก์ Google Maps -->
            <a :href="'https://www.google.com/maps/place/?q=place_id:' + place.place_id" target="_blank"
             class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 mt-auto custom-btn-size"
              title="เปิดใน Google Maps">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                class="bi bi-pin-map-fill text-danger" viewBox="0 0 16 16">
                <path fill-rule="evenodd"
                  d="M3.1 11.2a.5.5 0 0 1 .4-.2H6a.5.5 0 0 1 0 1H3.75L1.5 15h13l-2.25-3H10a.5.5 0 0 1 0-1h2.5a.5.5 0 0 1 .4.2l3 4a.5.5 0 0 1-.4.8H.5a.5.5 0 0 1-.4-.8z" />
                <path fill-rule="evenodd" d="M4 4a4 4 0 1 1 4.5 3.969V13.5a.5.5 0 0 1-1 0V7.97A4 4 0 0 1 4 3.999z" />
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
const defaultImg = 'https://via.placeholder.com/400x300?text=No+Image'
const locationInput = ref('บางซื่อ')
const radius = ref(200) // ค่าเริ่มต้น 200 เมตร

const radii = [
  { label: '100 ม.', value: 100 },
  { label: '200 ม.', value: 200 },
  { label: '500 ม.', value: 500 },
  { label: '1 กิโล', value: 1000 },
]

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
    console.error('Error loading restaurants', e)
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
.custom-btn-size {
  padding: 4px 12px;
  font-size: 0.9rem;
  min-width: 100px; 
}

.map-icon-red {
  fill: red;
}
</style>
