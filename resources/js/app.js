import './bootstrap'; 
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js' 
import { createApp } from 'vue';
import axios from 'axios';
import ExampleComponent from './Components/ExampleComponent.vue';

axios.defaults.baseURL = 'http://localhost:8000';

createApp(ExampleComponent).mount('#app');
