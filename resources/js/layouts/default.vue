<template>
  <div class="main-layout">
    <navbar />

    <div class="container mt-4">
      <child />
    </div>
    <div class="footer">
      <Footer></Footer>
    </div>
  </div>
</template>

<script>
import Navbar from '~/components/Navbar'
import Footer from '../components/Footer';

export default {
  name: 'MainLayout',

  components: {
    Footer,
    Navbar
  }
}
</script>
