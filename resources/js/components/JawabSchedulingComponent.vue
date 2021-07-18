<template>
  <div style="width: 200px">
    <div class="form-group">
      <label for="schedule" class="col-form-label text-md-right">Send to eligible users</label>
      <input id="schedule" class="form-control" type="text" name="schedule[type]" v-model="schedule.type" @click="scheduling = !scheduling" />
    </div>
    <div v-if="schedule.type === 'Scheduled'">
      <ejs-datetimepicker :placeholder="waterMark" v-model="schedule.date" :min="new Date()" :format="'dd/MM/yyyy HH:mm'" :timeFormat="'HH:mm'" ></ejs-datetimepicker>
      <input type="hidden" name="schedule[date]" v-model="schedule.date.toISOString()" />
    </div>
    <div v-if="scheduling">
      <ul class="list-group">
        <li class="list-group-item disabled">One time notification</li>
        <li class="list-group-item list-group-item-action" :class="{ active: schedule.type === 'Now' }" @click="schedule.type = 'Now'; scheduling = !scheduling">Now</li>
        <li class="list-group-item list-group-item-action" :class="{ active: schedule.type === 'Scheduled' }" @click="schedule.type = 'Scheduled'; scheduling = !scheduling">Scheduled</li>
        <!--                <li class="list-group-item disabled">Recurring notifications</li>-->
        <!--                <li class="list-group-item list-group-item-action" :class="{ active: schedule.type === 'Daily' }" @click="schedule.type = 'Daily'; scheduling = !scheduling">Daily</li>-->
        <!--                <li class="list-group-item list-group-item-action" :class="{ active: schedule.type === 'Custom' }" @click="schedule.type = 'Custom'; scheduling = !scheduling">Custom..</li>-->
      </ul>
    </div>
  </div>
</template>

<script>
export default {
  name:'JawabSchedulingComponent',
  props: {
    propSchedule: String,
  },
  data() {
    return {
      scheduling: false,
      schedule: {
        type: this.oldVal("type","Now"),
        date: new Date(this.oldVal("date", (new Date()).toISOString())),
      },
      waterMark: 'Select a datetime',
    }
  },
  methods: {
    oldVal: function (prop, defaultVal) {
      let schedule = JSON.parse(this.propSchedule);
      if(schedule) {
        return schedule[prop] ?? defaultVal ?? null;
      }
      return defaultVal ?? null;
    }
  }
}
</script>

<style scoped>

</style>
