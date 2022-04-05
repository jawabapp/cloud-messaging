<template>
  <div class="row">
    <div class="col-md-4 border-right">
      <select v-model="type" class="custom-select audience">
        <option value="">Select ...</option>
        <option v-for="(item,index) in types" :key="index" :value="item.value" :disabled="disabledType(item)">{{item.label}}</option>
      </select>
      <input type="hidden" :name="`target[app][${appKey}][and][${audienceKey}][type]`" v-model="type" >
    </div>
    <template v-if="typeObject">
      <div class="col-md-4 border-right">
        <select v-model="condition" :name="`target[app][${appKey}][and][${audienceKey}][condition]`" class="custom-select audience">
          <option v-for="(condition,index) in typeObject.conditions" :key="index" :value="condition.value">{{condition.label}}</option>
        </select>
      </div>
      <div class="col-md-4">
        <template v-if="typeObject.type == 'MULTI_SELECT'">
          <ejs-multiselect
              id='multiselect'
              class='audience'
              mode="CheckBox"
              :name="`target[app][${appKey}][and][${audienceKey}][options][]`"
              :placeholder="`Select ${typeObject.selectLabel} ...`"
              :dataSource='options'
              :fields='fields'
              :allowFiltering='true'
              :showSelectAll='true'
              v-model="values"
              selectAllText="Select All"
              unSelectAllText="unSelect All">
          </ejs-multiselect>
        </template>
        <template v-else-if="typeObject.type == 'SINGLE_SELECT'">
            <ejs-dropdownlist
              id='dropdownlist'
              ref='dropdown'
              :name="`target[app][${appKey}][and][${audienceKey}][options][]`"
              :placeholder="`Select ${typeObject.selectLabel} ...`"
              :dataSource='options'
              :fields='fields'
              v-model="values"
              :allowFiltering='true'>
              </ejs-dropdownlist>
        </template>
      </div>
    </template>
    <template v-else>
      <div class="col-md-8"></div>
    </template>
  </div>
</template>

<script>
import axios from "axios";
export default {
  name:"JawabTargetAppRowComponent",
  props: {
    appKey: {
      type: Number,
      required: true,
      default: 1
    },
    app: {
      type: Object,
      required: true,
    },
    audienceKey: {
      type: Number,
      required: true,
      default: 0
    },
    audience: {
      type: Object,
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
    },
    os: {
      type: String,
      required: true,
      default: 'ios'
    },

  },
  data() {
    return {
      fields : { text: 'text', value: 'value' },
      options: [],
      type: '',
      typeObject: null,
      condition: null,
      values:[]
    }
  },
  mounted(){
    this.type = this.audience.type || '';
    this.condition = this.audience.condition || null;
    this.values = this.audience.options || [];
  },
  watch: {
    type(val) {
      this.$emit('changeType', this.audience, val);

      this.typeObject = this.types.find(type => (type.value === val));
      this.getOptions(val);
    }
  },
  methods: {
    disabledType(type) {
      return Object.values(this.appTypes).includes(type.value)
    },
    getOptions(type) {
      if (this.typeObject.options && this.typeObject.options.length) {
        this.options = this.typeObject.options
      } else {
        let os = document.getElementById(`audience-os-${this.app}`);
        axios.get(`${this.filterPrefixUrl}/${type}?os=${this.os}`)
            .then(res => {
              this.options = res.data
            }).catch(err => {
          console.log(err)
        });
      }
    }
  }
}
</script>
