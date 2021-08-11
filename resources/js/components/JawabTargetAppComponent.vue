<template>
  <div class="card">
    <div class="card-body bg-light">
      <div class="row">
        <div class="col-md-4 border-right">
          <span style="line-height: 37px;">APP</span>
        </div>
        <div class="col-md-6">
          <select :name="`target[app][${app}][os]`" class="custom-select audience" v-model="os" :id="`audience-os-${app}`">
            <option value="ios">iOS</option>
            <option value="android">Android</option>
          </select>
        </div>
        <div class="col-md-2 text-left">
          <a href="#" @click.prevent="and" class="btn btn-link">and</a>
          <a href="#" @click.prevent="removeApp(app)" class="btn btn-link">remove</a>
        </div>
      </div>
      <div v-for="(audience, index) in audiences" :key="index" class="row mt-2 pt-2 border-top">
        <div class="col-md-10">
          <jawab-target-app-row :types="types" :filter-prefix-url="filterPrefixUrl" :app="app" :audience="audience" :appTypes="appTypes" @changeType="changeType"/>
        </div>
        <div class="col-md-2 text-left">
          <a href="#" @click.prevent="and" class="btn btn-link" v-if="audience < types.length">and</a>
          <a href="#" @click.prevent="remove(audience)" class="btn btn-link">remove</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import JawabTargetAppRowComponent from "./JawabTargetAppRowComponent";
export default {
  name:"JawabTargetAppComponent",
  components:{
    'jawab-target-app-row' : JawabTargetAppRowComponent
  },
  props: {
    app: {
      type: Number,
      required: true,
      default: 1
    },
    types:{
      type:Array,
      required:true,
      default: []
    },
    filterPrefixUrl:{
      type:String,
      required:true
    }
  },
  data() {
    return {
      os: 'ios',
      audiences: [],
      appTypes: {}
    }
  },
  watch: {
    os(newVal, oldVal) {
      if(newVal !== oldVal) {
        this.audiences = []
        this.appTypes = {}
      }
    }
  },
  methods: {
    and() {
      this.audiences.push(this.audiences.length + 1)
    },
    remove(audience) {
      this.appTypes[audience] = undefined
      this.audiences.splice(this.audiences.indexOf(audience), 1)
    },
    removeApp(app) {
      this.$emit('remove', app);
    },
    changeType(audience, type) {
      this.appTypes[audience] = type
    }
  }
}
</script>

<style scoped>

</style>
