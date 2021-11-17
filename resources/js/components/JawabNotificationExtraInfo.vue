<template>
  <div class="row">
    <div class="col-md-8">
      <div class="card-body">
        <div v-for="(field, field_key) in fields" class="form-group" :key="field_key">
          <label :for="field.name" class="col-form-label text-md-right">Notification {{ field.label }}</label>
          <input :id="field.name" type="text" class="form-control" :class="{ 'is-invalid' : errors.hasOwnProperty('extra_info.' + field.name) }" :name="'extra_info[' + field.name + ']'" v-model="field.model"/>
          <jawab-character-counter :text="field.model" ></jawab-character-counter>
          <template v-if="errors.hasOwnProperty('extra_info.' + field.name)">
            <span v-for="(error, index) in errors['extra_info.' + field.name]" :key="index" class="invalid-feedback" role="alert"><strong>{{ error }}</strong></span>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import JawabCharacterCounterComponent from "./JawabCharacterCounterComponent";
export default {
  name:"JawabNotificationExtraInfo",
  components:{
    'jawab-character-counter': JawabCharacterCounterComponent
  },
  props: {
    jsonData:{
      type: String,
      required: false,
      default: ''
    },
    jsonErrors: {
      type: String,
      required: false,
      default: ''
    },
  },
  data() {
    return {
      fields : [],
      errors : {}
    }
  },
  created() {
    if (this.jsonData) {
      let jsonData = JSON.parse(this.jsonData);
      if (typeof jsonData === 'object') {
        for (const [key, value] of Object.entries(jsonData)) {
          this.fields.push({
            label: key.charAt(0).toUpperCase() + key.slice(1),
            name: key,
            model: value
          })
        }
      }
    }

    if (this.jsonErrors) {
      let jsonErrors = JSON.parse(this.jsonErrors);
      if (typeof jsonErrors === 'object') {
        this.errors = jsonErrors;
      }
    }
  }
}
</script>
