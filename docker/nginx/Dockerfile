FROM nginx:1.15.11-alpine
LABEL maintainer="tg@google.com"

# set timezome
ENV TZ=Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone