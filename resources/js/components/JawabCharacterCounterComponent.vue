<template>
  <span class="limiter">{{charactersLeft}}</span>
</template>

<script>
export default {
  name:"JawabCharacterCounterComponent",
  props: {
    text: String,
    limit: [String,Number],
  },
  computed: {
    charactersLeft() {

      let char = (this.text ?? '').length,
          limit = parseInt(this.limit) ?? 140;

      return (limit - char) + " / " + limit + " characters remaining";
    }
  }
}
</script>

<style scoped>

</style>
