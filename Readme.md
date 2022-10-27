# Backend-api

Simple backend api for develop frontend application

## How run

Create directory for you data, for example
```bash
mkdir ./data
cd data
```

Easy usage docker for see example
```bash
sudo docker run --name=simple-backend-api --rm -p 8765:80 itlab77/backend-api
```
and example route
http://localhost:8765/v1/questions/1

For your data need usage
```bash
sudo docker run -d --name=simple-backend-api --rm -v "$PWD":/data -p 8765:80 itlab77/backend-api
```
and create files like see below

## How add route and data

Create files in your ./data directory, like this
```bash
touch customers.get.200.json
```
And copy to customers.get.200.json file valid json data, like
```bash
echo '{"name":"mkyong.com","messages":["msg 1","msg 2","msg 3"],"age":100}' >> customers.get.200.json
```

That's all. Your data access on route http://localhost:8765/customers

## Advanced usage

Examples map files to route:

- customers.get.200.json - GET /customers
- customers.post.200.json - POST /customers
- customers.put.200.json - PUT /customers
- customers.patch.200.json - PATCH /customers
- customers.delete.200.json - DELETE /customers

where 200 - response status code

- v1.customers.1.get.200.json - GET /v1/customers/1
 
and etc.