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
touch customers.get.json
```
And copy to customers.get.json file valid json data, like
```bash
echo '{"name":"mkyong.com","messages":["msg 1","msg 2","msg 3"],"age":100}' >> customers.get.json
```

That's all. Your data access on route http://localhost:8765/customers

## Advanced usage

Examples map files to route:

- customers.get.json - GET /customers
- customers.post.json - POST /customers
- customers.put.json - PUT /customers
- customers.patch.json - PATCH /customers
- customers.delete.json - DELETE /customers


- v1.customers.1.get.json - GET /v1/customers/1
 
and etc.