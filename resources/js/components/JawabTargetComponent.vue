<template>
  <div>
    <div>
      <div v-for="(app, index) in apps" :key="index" class="mb-3">
        <jawab-target-app :types="types" :filter-prefix-url="filterPrefixUrl" :app="app" @remove="remove" />
      </div>
      <a href="#" @click.prevent="addApp" class="btn btn-primary">Target another audience</a>
      <a href="#" @click.prevent="checkCount" class="btn btn-outline-primary">refresh</a>
    </div>
    <div v-if="audienceCount" class="alert alert-info mt-2" role="alert">
      the targeted audience count is {{ audienceCount }}
    </div>
  </div>
</template>

<script>
import JawabTargetAppComponent from "./JawabTargetAppComponent";
export default {
  name:"JawabTargetComponent",
  components:{
    'jawab-target-app' : JawabTargetAppComponent
  },
  props:{
    types:{
      type:Array,
      required:true
    },
    targetAudienceUrl:{
      type:String,
      required:true
    },
    filterPrefixUrl:{
      type:String,
      required:true
    }
  },
  data() {
    return {
      apps: [],
      audienceCount: 0,
    }
  },
  methods: {
    addApp() {
      this.apps.push(this.apps.length + 1)
    },
    remove(app) {
      this.apps.splice(this.apps.indexOf(app), 1)
    },
    checkCount() {
      axios.post(this.targetAudienceUrl, $("form#compose_notifications_form").serialize())
          .then(res => {
            this.audienceCount = res.data
          }).catch(err => {
        console.log(err)
      });
    }
  }
}
</script>

<style scoped>

</style>
