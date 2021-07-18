<template>
  <div class="row">
    <div class="col-md-4 border-right">
      <select v-model="type" class="custom-select audience">
        <option value="">Select ...</option>
        <option v-for="(item,index) in types" :key="index" :value="item.value" :disabled="disabledType(item)">{{item.label}}</option>
      </select>
      <input type="hidden" :name="`target[app][${app}][and][${audience}][type]`" v-model="type" >
    </div>
    <template v-if="typeObject">
      <div class="col-md-4 border-right">
        <select :name="`target[app][${app}][and][${audience}][condition]`" class="custom-select audience">
          <option v-for="(condition,index) in typeObject.conditions" :key="index" :value="condition.value">{{condition.label}}</option>
        </select>
      </div>
      <div class="col-md-4">
        <ejs-multiselect
            id='multiselect'
            class='audience'
            mode="CheckBox"
            :name="`target[app][${app}][and][${audience}][options][]`"
            :placeholder="`Select ${typeObject.selectLabel} ...`"
            :dataSource='options'
            :fields='fields'
            :allowFiltering='true'
            :showSelectAll='true'
            selectAllText="Select All"
            unSelectAllText="unSelect All">
        </ejs-multiselect>
      </div>
    </template>
    <template v-else>
      <div class="col-md-8"></div>
    </template>
  </div>
</template>

<script>
export default {
  name:"JawabTargetAppRowComponent",
  props: {
    app: {
      type: Number,
      required: true,
      default: 1
    },
    audience: {
      type: Number,
      required: true,
      default: 0
    },
    types: {
      type: Array,
      required: true,
      default: []
    },
    appTypes: {
      type: Object,
      required: true,
      default: {}
    },
    filterPrefixUrl:{
      type:String,
      required:true,
      default: '/api'
    }
  },
  data() {
    return {
      fields : { text: 'text', value: 'value' },
      options: [],
      type: '',
      typeObject: null
    }
  },
  watch: {
    type(val) {
      this.$emit('changeType', this.audience, val);

      this.getOptions(val);
      this.typeObject = this.types.find(type => (type.value === val));
    }
  },
  methods: {
    disabledType(type) {
      return Object.values(this.appTypes).includes(type.value)
    },
    getOptions(type) {
      let os = document.getElementById(`audience-os-${this.app}`);

      axios.get(`${this.filterPrefixUrl}/${type}?os=${os.value}`)
          .then(res => {
            this.options = res.data
          }).catch(err => {
        console.log(err)
      });
    }
  }
}
</script>

<style scoped>

</style>
