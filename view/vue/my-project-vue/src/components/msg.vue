<template>
  <div class="hello">
    <h1 v-on:click="setmsg">{{ msg }}</h1>

  </div>
</template>

<script>
export default {
  // name: 'hello',
  props:['params'],
  data () {
    return {
      msg:'Hello Carl !'
    }
  },
  watch:{
    params:{
      handler:function(params){
        this.msg=params
      },
      deep:true
    }
  },
  methods:{
    setmsg:function(){
      this.msg='Hello Carl !'
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
h1, h2 {
  font-weight: normal;
}

ul {
  list-style-type: none;
  padding: 0;
}

li {
  display: inline-block;
  margin: 0 10px;
}

a {
  color: #42b983;
}
</style>
