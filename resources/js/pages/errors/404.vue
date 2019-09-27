<template>
  <div>
    <b-card
            title="This page seems to be lost."
            img-src="https://www.memorabletv.com/wp-content/uploads/2017/03/seinfeld-the-parking-garage.jpg"
            img-alt="Page not found"
            style="max-width: 600px;"
            class="mb-2"
    >
      <b-card-text>
        <blockquote class="blockquote">
          <p><em>What?</em></p>
          <p><em>Where's the car?</em></p>
          <p><em>I thought it was here.</em></p>
          <p><em>You don't know where we parked?</em></p>
          <p><em>Oh, this is great.</em></p>
        </blockquote>
      </b-card-text>

      <router-link tag="a" class="btn btn-warning" to="/">Head home</router-link>
    </b-card>
  </div>
</template>

<script>
export default { }
</script>
