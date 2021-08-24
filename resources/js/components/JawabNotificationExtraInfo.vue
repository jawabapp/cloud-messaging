<template>
  <div class="row">
    <div class="col-md-8">
      <div class="card-body">
        <div class="form-group">
          <label for="name" class="col-form-label text-md-right">{{ extraInfo.nameLabel }}</label>
          <input id="name" type="text" class="form-control" :class="{ 'is-invalid' : errorName }" name="extra_info[name]" v-model="extraInfo.nameModel"/>
          <jawab-character-counter :text="extraInfo.nameModel" :limit="140" ></jawab-character-counter>
          <span v-if="errorName" class="invalid-feedback" role="alert"><strong>{{ errorName }}</strong></span>
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
    name:{
      type: String,
      required: false,
      default: ''
    },
    errorName: {
      type: String,
      required: false,
      default: ''
    },
  },
  data() {
    return {
      extraInfo:{
        nameLabel: 'Notification Name',
        nameModel: '',
      }
    }
  },
  created() {
    this.extraInfo.nameModel = this.name;
  }
}
</script>
