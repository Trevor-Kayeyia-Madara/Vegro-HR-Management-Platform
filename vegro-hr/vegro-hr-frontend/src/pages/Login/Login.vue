<!-- eslint-disable vue/multi-word-component-names -->
<script setup>

import { login } from "../../services/AuthService";

const testLogin = async () => {

const response = await login({
email: "admin@test.com",
password: "password"
})

console.log(response.data)

}

testLogin()

</script>