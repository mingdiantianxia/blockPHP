<script src="store.js"></script>
<template>
  <div id="app2">
  <header>
    <!-- router-link 定义点击后导航到哪个路径下 -->
    <router-link to="/home">Home</router-link>
  </header>
  <h1 v-text='title'></h1>
  <input type="text" name="" id="" v-model="newItem" v-on:keyup.enter="addNew" lazy number>
  <div>{{dosomething}}</div>
  <ul>
    <li v-for="(item, index) in items" v-bind:class="{finished:item.isFinished}" v-on:click="toggleFinish(item)" v-on:dblclick="removeItem(index)">
      {{index}}--{{item.label}}
    </li>
  </ul>
  <msg v-bind:params="newItem"></msg>
  
  <child v-bind:message="newItem"></child>

   <!-- 对应的组件内容渲染到router-view中 -->
  <router-view></router-view>   
  </div>
</template>

<script>
import Store from './store.js'
import Msg from './components/msg'
export default {
   data () {
    return {
      title:'Hello Vue!',
      items: Store.fetch(),
      newItem:'',

    }
  },
  created:function(){
        console.log(this);
      },
  watch:{
    items:{
      handler:function(items){
        Store.save(items);
      },
      deep:true
    }
  },
  methods:{
    toggleFinish:function(item){
      item.isFinished = !item.isFinished
    },
    removeItem:function(index){
      this.items.splice(index,1)
    },
    addNew:function (){
      this.items.push({label:this.newItem,isFinished:false})
      this.newItem = ''

    }
  },
  components:{
   Msg,Child:{props:['message'],template:'<span>{{ message }}</span>'}
  },
  computed:{
    dosomething:function(){
      return `冥殿写前端:${this.newItem}`
    }
  }
}
</script>

<style>
.finished{
  text-decoration: underline;
}
#app2 {
  font-family: 'Avenir', Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  width:20%;
  margin:auto;
  color: #2c3e50;
  margin-top: 60px;
}
</style>
